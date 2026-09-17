<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\MatchGame;
use App\Models\MatchSelection;
use App\Models\Season;
use App\Models\SeasonTeam;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function home(Request $request): View
    {
        $leagueTeam = $request->user() ? $this->teamFor($request) : null;

        return view('welcome', compact('leagueTeam'));
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
        $team = $this->teamFor($request);
        $myTeamStanding = SeasonTeam::query()
            ->where('team_id', $team->id)
            ->where('season_league_id', $seasonLeague->id)
            ->first();

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

        $players = $team->players()->with('player')->get();
        $nextMatch = $matches->first(fn ($match) => $match->status === 'scheduled' && $match->scheduled_at->isFuture() && ($match->home_team_id === $team->id || $match->away_team_id === $team->id));
        $selection = $nextMatch?->selections()->where('team_id', $team->id)->with('players')->first();

        return view('league.index', compact('season', 'league', 'seasonLeague', 'team', 'myTeamStanding', 'standings', 'matches', 'players', 'nextMatch', 'selection'));
    }

    public function match(Request $request, string $leagueSlug, MatchGame $match): View
    {
        abort_unless($match->seasonLeague->league->slug === $leagueSlug, 404);

        $season = Season::query()->where('status', 'active')->with('seasonLeagues.league')->firstOrFail();
        $league = $match->seasonLeague->league;
        $team = $this->teamFor($request);
        $players = $team->players()->with('player')->get();
        $selection = $match->selections()->where('team_id', $team->id)->with('players')->first();

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
        $season = Season::query()->where('status', 'active')->firstOrFail();
        $podworkowa = League::query()->where('level', 11)->firstOrFail();
        $seasonLeague = $season->seasonLeagues()->where('league_id', $podworkowa->id)->firstOrFail();
        SeasonTeam::firstOrCreate(['season_league_id' => $seasonLeague->id, 'team_id' => $team->id]);

        return $team;
    }
}
