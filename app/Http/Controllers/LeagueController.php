<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueRound;
use App\Models\MatchGame;
use App\Models\MatchSelection;
use App\Models\Season;
use App\Models\SeasonLeague;
use App\Models\SeasonRound;
use App\Models\SeasonTeam;
use App\Models\Team;
use App\Models\User;
use App\Services\RoundBonusQuestionService;
use App\Services\SwissLeagueService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function home(Request $request): View
    {
        $season = Season::query()->where('status', 'active')->with('seasonLeagues.league')->firstOrFail();
        $league = League::query()->where('slug', 'ekstraklasa')->firstOrFail();
        $seasonLeague = $season->seasonLeagues()->where('league_id', $league->id)->firstOrFail();
        $this->ensureSeasonLeagueMatches($seasonLeague);
        $leagueTeam = $request->user() ? $this->teamFor($request) : null;
        $standings = SeasonTeam::query()
            ->where('season_league_id', $seasonLeague->id)
            ->with('team')
            ->orderByDesc('points')
            ->orderByDesc('score_for')
            ->orderBy('team_id')
            ->get();
        $leaguePositions = $seasonLeague->positions()->with('team')->get();
        $leagueMatches = $seasonLeague->matches()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at')
            ->get();

        return view('welcome', compact('leagueTeam', 'season', 'league', 'standings', 'leaguePositions', 'leagueMatches'));
    }

    public function index(Request $request): View
    {
        $season = Season::query()->where('status', 'active')->with('seasonLeagues.league')->firstOrFail();
        $defaultLeague = League::query()->where('slug', 'ekstraklasa')->firstOrFail();

        return $this->show($request, $defaultLeague->slug);
    }

    public function show(Request $request, string $leagueSlug): View
    {
        $season = Season::query()->where('status', 'active')->with('seasonLeagues.league')->firstOrFail();
        $league = League::query()->where('slug', $leagueSlug)->firstOrFail();
        $seasonLeague = $season->seasonLeagues()->where('league_id', $league->id)->firstOrFail();
        $this->ensureSeasonLeagueMatches($seasonLeague);
        $team = $request->user() ? $this->teamFor($request) : null;
        $myTeamStanding = $team ? SeasonTeam::query()
            ->where('team_id', $team->id)
            ->where('season_league_id', $seasonLeague->id)
            ->first() : null;

        $standings = SeasonTeam::query()
            ->where('season_league_id', $seasonLeague->id)
            ->with('team')
            ->orderByDesc('points')
            ->orderByDesc('score_for')
            ->orderBy('team_id')
            ->get();

        $matches = MatchGame::query()
            ->where('season_league_id', $seasonLeague->id)
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at')
            ->get();
        $seasonRounds = SeasonRound::query()
            ->where('season_id', $season->id)
            ->with('realMatch')
            ->orderBy('round_number')
            ->get();

        $players = $team ? $team->players()->with('player')->get() : collect();
        $nextMatch = $matches->first(fn ($match) => $match->status === 'scheduled' && $match->scheduled_at->isFuture() && (! $team || $match->home_team_id === $team->id || $match->away_team_id === $team->id));
        $selection = $team && $nextMatch ? $nextMatch->selections()->where('team_id', $team->id)->with('players')->first() : null;

        return view('league.index', compact('season', 'league', 'seasonLeague', 'team', 'myTeamStanding', 'standings', 'matches', 'seasonRounds', 'players', 'nextMatch', 'selection'));
    }

    public function match(Request $request, string $leagueSlug, MatchGame $match): View
    {
        abort_unless($match->seasonLeague->league->slug === $leagueSlug, 404);

        $season = Season::query()->where('status', 'active')->with('seasonLeagues.league')->firstOrFail();
        $league = $match->seasonLeague->league;
        $team = $request->user() ? $this->teamFor($request) : null;
        $players = $team ? $team->players()->with('player')->get() : collect();
        $selection = $team ? $match->selections()->where('team_id', $team->id)->with('players')->first() : null;

        return view('league.match', compact('season', 'league', 'match', 'team', 'players', 'selection'));
    }

    public function submitSelection(Request $request, MatchGame $match): RedirectResponse
    {
        $team = $this->teamFor($request);
        abort_unless($match->home_team_id === $team->id || $match->away_team_id === $team->id, 403);
        abort_unless($match->status === 'scheduled' && $match->scheduled_at->isFuture(), 422, 'Typowanie zostało zamknięte.');

        $data = $request->validate(['players' => ['required', 'array', 'size:5'], 'players.*' => ['integer', 'distinct', 'exists:players,id']]);
        $availablePlayerIds = $team->players()->where(function ($query): void {
            $query->whereNull('injury_until')->orWhere('injury_until', '<=', now());
        })->pluck('player_id');
        abort_unless(collect($data['players'])->every(fn (int $playerId): bool => $availablePlayerIds->contains($playerId)), 422, 'Wybrano zawodnika niedostępnego.');

        app(DatabaseManager::class)->transaction(function () use ($data, $match, $team): void {
            $selection = MatchSelection::updateOrCreate(
                ['match_id' => $match->id, 'team_id' => $team->id],
                ['submitted_at' => now()],
            );
            $selection->players()->delete();
            $selection->players()->createMany(array_map(fn (int $playerId): array => ['player_id' => $playerId], $data['players']));
        });

        return back()->with('status', 'Skład pięciu zawodników został zapisany.');
    }

    private function teamFor(Request $request): Team
    {
        $user = $request->user();
        $team = Team::firstOrCreate(['user_id' => $user->id], ['name' => $user->name]);

        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return $team;
        }

        $season = Season::query()->where('status', 'active')->firstOrFail();
        $podworkowa = League::query()->where('level', 11)->firstOrFail();
        $seasonLeague = $season->seasonLeagues()->where('league_id', $podworkowa->id)->firstOrFail();
        SeasonTeam::firstOrCreate(['season_league_id' => $seasonLeague->id, 'team_id' => $team->id]);

        return $team;
    }

    private function ensureSeasonLeagueMatches(SeasonLeague $seasonLeague): void
    {
        $seasonLeague->loadMissing('league');

        if ($seasonLeague->league->level === 11) {
            app(SwissLeagueService::class)->generateNextRound($seasonLeague);

            return;
        }

        if ($seasonLeague->rounds()->exists() || $seasonLeague->matches()->exists()) {
            return;
        }

        $positions = $seasonLeague->positions()->orderBy('position')->get();
        if ($positions->isEmpty()) {
            for ($position = 1; $position <= 10; $position++) {
                $seasonLeague->positions()->create([
                    'position' => $position,
                    'bot_name' => 'Chłopaki z orlika',
                    'inherited_points' => 0,
                ]);
            }
            $positions = $seasonLeague->positions()->orderBy('position')->get();
        }

        foreach ($positions as $position) {
            if ($position->team_id !== null) {
                continue;
            }

            $botUser = User::query()->firstOrCreate(
                ['email' => sprintf('bot.%d.%d@local.test', $seasonLeague->id, $position->position)],
                [
                    'name' => 'Chłopaki z orlika',
                    'password' => bcrypt('bot-'.$seasonLeague->id.'-'.$position->position),
                    'role' => 'user',
                ],
            );

            $botTeam = Team::firstOrCreate(['user_id' => $botUser->id], ['name' => $position->bot_name ?: 'Chłopaki z orlika']);
            $position->update(['team_id' => $botTeam->id]);
        }

        $teamIds = $positions->pluck('team_id')->filter()->values()->all();
        if ($teamIds === []) {
            return;
        }

        $roundTeams = $teamIds;
        if (count($roundTeams) % 2 !== 0) {
            $roundTeams[] = null;
        }

        $roundCount = count($roundTeams) - 1;
        for ($round = 0; $round < $roundCount; $round++) {
            $leagueRound = LeagueRound::create([
                'season_league_id' => $seasonLeague->id,
                'round_number' => $round + 1,
                'scheduled_at' => now()->addDays($round),
            ]);
            app(RoundBonusQuestionService::class)->assignRandomQuestions($leagueRound);

            $half = intdiv(count($roundTeams), 2);
            for ($index = 0; $index < $half; $index++) {
                $homeTeamId = $roundTeams[$index];
                $awayTeamId = $roundTeams[count($roundTeams) - 1 - $index];
                if ($homeTeamId === null || $awayTeamId === null) {
                    continue;
                }

                MatchGame::create([
                    'season_league_id' => $seasonLeague->id,
                    'league_round_id' => $leagueRound->id,
                    'round_number' => $round + 1,
                    'home_team_id' => $homeTeamId,
                    'away_team_id' => $awayTeamId,
                    'scheduled_at' => now()->addDays($round),
                    'status' => 'scheduled',
                ]);
            }

            $lastTeamId = array_pop($roundTeams);
            array_splice($roundTeams, 1, 0, [$lastTeamId]);
        }
    }
}
