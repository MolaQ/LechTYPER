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
        $team = $this->teamFor($request);
        $seasonTeam = SeasonTeam::query()->where('team_id', $team->id)->whereHas('seasonLeague', fn ($query) => $query->where('season_id', $season->id))->with('seasonLeague.league')->first();
        $players = $team->players()->with('player')->get();
        $nextMatch = MatchGame::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->where(fn ($query) => $query->where('home_team_id', $team->id)->orWhere('away_team_id', $team->id))
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at')
            ->first();
        $selection = $nextMatch?->selections()->where('team_id', $team->id)->with('players')->first();

        return view('league.index', compact('season', 'team', 'seasonTeam', 'players', 'nextMatch', 'selection'));
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
