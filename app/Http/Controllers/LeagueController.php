<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueRound;
use App\Models\LechMatch;
use App\Models\MatchGame;
use App\Models\MatchSelection;
use App\Models\MatchSelectionAnswer;
use App\Models\Season;
use App\Models\SeasonLeague;
use App\Models\SeasonRound;
use App\Models\SeasonTeam;
use App\Models\Team;
use App\Models\User;
use App\Services\RoundBonusQuestionService;
use App\Services\SeasonTeamCleanupService;
use App\Services\SwissLeagueService;
use App\Services\SyncRealMatchToLechTyperService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LeagueController extends Controller
{
    public function home(Request $request): View
    {
        $season = Season::query()->where('status', 'active')->with('seasonLeagues.league')->firstOrFail();
        $league = League::query()->where('slug', 'ekstraklasa')->firstOrFail();
        $seasonLeague = $season->seasonLeagues()->where('league_id', $league->id)->firstOrFail();
        $this->ensureSeasonLeagueMatches($seasonLeague);
        $season->realMatches()->get()->each(fn ($realMatch) => app(SyncRealMatchToLechTyperService::class)->sync($realMatch));
        $nextTyperMatch = LechMatch::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->with('competition')
            ->orderBy('scheduled_at')
            ->first();
        $leagueTeam = $request->user() ? $this->teamFor($request) : null;
        $standingsQuery = SeasonTeam::query()
            ->where('season_league_id', $seasonLeague->id)
            ->with('team')
            ->orderByDesc('points')
            ->orderByDesc(DB::raw('score_for - score_against'))
            ->orderByDesc('score_for')
            ->orderByDesc('bonus_points')
            ->orderByDesc('wins')
            ->orderByDesc('draws')
            ->orderBy('team_id');
        $allStandings = $standingsQuery->get();
        $perPage = 5;
        $page = max(1, $request->integer('standings_page', 1));
        if ($leagueTeam && ! $request->has('standings_page')) {
            $teamIndex = $allStandings->search(fn ($standing) => $standing->team_id === $leagueTeam->id);
            if ($teamIndex !== false) {
                $page = intdiv($teamIndex, $perPage) + 1;
            }
        }
        $standings = new LengthAwarePaginator(
            $allStandings->forPage($page, $perPage)->values(),
            $allStandings->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'standings_page', 'query' => $request->query()],
        );
        $standings->withQueryString();
        $leaguePositions = $seasonLeague->positions()->with('team')->get();
        $leagueMatches = $seasonLeague->matches()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at')
            ->get();

        return view('welcome', compact('leagueTeam', 'season', 'league', 'standings', 'leaguePositions', 'leagueMatches', 'nextTyperMatch'));
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

        $standingsQuery = SeasonTeam::query()
            ->where('season_league_id', $seasonLeague->id)
            ->with('team')
            ->orderByDesc('points')
            ->orderByDesc(DB::raw('score_for - score_against'))
            ->orderByDesc('score_for')
            ->orderByDesc('bonus_points')
            ->orderByDesc('wins')
            ->orderByDesc('draws')
            ->orderBy('team_id');

        if ($league->level === 11) {
            $allStandings = $standingsQuery->get();
            $perPage = 10;
            $page = max(1, $request->integer('standings_page', 1));
            if ($team && ! $request->has('standings_page')) {
                $teamIndex = $allStandings->search(fn ($standing) => $standing->team_id === $team->id);
                if ($teamIndex !== false) {
                    $page = intdiv($teamIndex, $perPage) + 1;
                }
            }

            $standings = new LengthAwarePaginator(
                $allStandings->forPage($page, $perPage)->values(),
                $allStandings->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'pageName' => 'standings_page'],
            );
            $standings->withQueryString();
        } else {
            $standings = $standingsQuery->get();
        }

        $matches = MatchGame::query()
            ->where('season_league_id', $seasonLeague->id)
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at')
            ->get();
        $seasonRounds = $league->level === 11
            ? $seasonLeague->rounds()->with(['matches.homeTeam', 'matches.awayTeam', 'bonusQuestions'])->get()
            : SeasonRound::query()
                ->where('season_id', $season->id)
                ->with('realMatch')
                ->orderBy('round_number')
                ->get();
        $typerMatchesByRound = LechMatch::query()
            ->whereNotNull('round_number')
            ->with('competition')
            ->get()
            ->keyBy('round_number');

        $nextMatch = $matches->first(fn ($match) => $match->status === 'scheduled' && $match->scheduled_at->isFuture() && (! $team || $match->home_team_id === $team->id || $match->away_team_id === $team->id));
        $nextMatch?->loadMissing('leagueRound.bonusQuestions');
        $selection = $team && $nextMatch ? $nextMatch->selections()->where('team_id', $team->id)->with('answers')->first() : null;

        return view('league.index', compact('season', 'league', 'seasonLeague', 'team', 'myTeamStanding', 'standings', 'matches', 'seasonRounds', 'typerMatchesByRound', 'nextMatch', 'selection'));
    }

    public function match(Request $request, string $leagueSlug, MatchGame $match): View
    {
        abort_unless($match->seasonLeague->league->slug === $leagueSlug, 404);

        $season = Season::query()->where('status', 'active')->with('seasonLeagues.league')->firstOrFail();
        $league = $match->seasonLeague->league;
        $team = $request->user() ? $this->teamFor($request) : null;
        $match->loadMissing(['leagueRound.bonusQuestions', 'homeTeam.user', 'awayTeam.user', 'selections.team.user', 'selections.answers.question']);
        $selection = $team ? $match->selections()->where('team_id', $team->id)->with('answers')->first() : null;
        $isParticipant = $team !== null && in_array($team->id, [$match->home_team_id, $match->away_team_id], true);
        $homeTypingState = $match->selections->firstWhere('team_id', $match->home_team_id);
        $awayTypingState = $match->selections->firstWhere('team_id', $match->away_team_id);

        $homeSelection = $homeTypingState;
        $awaySelection = $awayTypingState;

        return view('league.match', compact('season', 'league', 'match', 'team', 'selection', 'isParticipant', 'homeSelection', 'awaySelection'));
    }

    public function realMatch(string $leagueSlug, LechMatch $match): View
    {
        $league = League::query()->where('slug', $leagueSlug)->firstOrFail();
        abort_unless($match->round_number !== null, 404);

        return view('league.real-match', [
            'league' => $league,
            'match' => $match->load(['competition', 'bonusQuestions']),
        ]);
    }

    public function submitSelection(Request $request, MatchGame $match): RedirectResponse
    {
        $team = $this->teamFor($request);
        abort_unless($match->home_team_id === $team->id || $match->away_team_id === $team->id, 403);
        abort_unless($match->status === 'scheduled' && $match->scheduled_at->isFuture(), 422, 'Typowanie zostało zamknięte.');

        $match->loadMissing('leagueRound.bonusQuestions');
        $questionIds = $match->leagueRound?->bonusQuestions->pluck('id') ?? collect();

        $data = $request->validate([
            'home_score' => ['required', 'integer', 'min:0', 'max:20'],
            'away_score' => ['required', 'integer', 'min:0', 'max:20'],
            'answers' => ['array'],
            'answers.*' => ['nullable', 'boolean'],
        ]);

        app(DatabaseManager::class)->transaction(function () use ($data, $match, $team, $questionIds): void {
            $selection = MatchSelection::updateOrCreate(
                ['match_id' => $match->id, 'team_id' => $team->id],
                ['home_score' => $data['home_score'], 'away_score' => $data['away_score'], 'submitted_at' => now()],
            );

            foreach ($questionIds as $questionId) {
                MatchSelectionAnswer::updateOrCreate(
                    ['match_selection_id' => $selection->id, 'league_round_bonus_question_id' => $questionId],
                    ['answer' => $data['answers'][$questionId] ?? null],
                );
            }
        });

        return back()->with('status', 'Typ na mecz został zapisany.');
    }

    private function teamFor(Request $request): Team
    {
        $user = $request->user();
        $team = Team::firstOrCreate(['user_id' => $user->id], ['name' => $user->name]);

        if (in_array($user->role, ['admin', 'superadmin'], true)) {
            return $team;
        }

        $season = Season::query()->where('status', 'active')->firstOrFail();
        // A team already assigned to any league this season must not be silently re-added to the backyard league.
        $alreadyAssigned = SeasonTeam::query()
            ->where('team_id', $team->id)
            ->whereHas('seasonLeague', fn ($query) => $query->where('season_id', $season->id))
            ->exists();

        if ($alreadyAssigned) {
            return $team;
        }

        $podworkowa = League::query()->where('level', 11)->firstOrFail();
        $seasonLeague = $season->seasonLeagues()->where('league_id', $podworkowa->id)->firstOrFail();
        SeasonTeam::create(['season_league_id' => $seasonLeague->id, 'team_id' => $team->id]);

        return $team;
    }

    private function ensureSeasonLeagueMatches(SeasonLeague $seasonLeague): void
    {
        $seasonLeague->loadMissing('league');
        app(SeasonTeamCleanupService::class)->reconcile($seasonLeague);

        if ($seasonLeague->league->level === 11) {
            app(SwissLeagueService::class)->simulateDueRounds($seasonLeague);
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
            SeasonTeam::firstOrCreate(['season_league_id' => $seasonLeague->id, 'team_id' => $botTeam->id], ['position' => $position->position]);
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
