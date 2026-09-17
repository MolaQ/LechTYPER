<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\Season;
use App\Models\SeasonLeague;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminLeagueManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.leagues.index', [
            'leagues' => League::query()->withCount('seasonLeagues')->orderBy('level')->get(),
            'seasons' => Season::query()->with('seasonLeagues.league')->orderByDesc('starts_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', 'unique:leagues,slug'],
            'level' => ['required', 'integer', 'min:1', 'max:255', 'unique:leagues,level'],
            'is_swiss' => ['sometimes', 'boolean'],
        ]);

        League::create($data + ['is_swiss' => $request->boolean('is_swiss')]);

        return back()->with('status', 'Liga została dodana.');
    }

    public function update(Request $request, League $league): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('leagues', 'slug')->ignore($league)],
            'level' => ['required', 'integer', 'min:1', 'max:255', Rule::unique('leagues', 'level')->ignore($league)],
            'is_swiss' => ['sometimes', 'boolean'],
        ]);

        $league->update($data + ['is_swiss' => $request->boolean('is_swiss')]);

        return back()->with('status', 'Liga została zaktualizowana.');
    }

    public function destroy(League $league): RedirectResponse
    {
        if ($league->seasonLeagues()->exists()) {
            return back()->withErrors(['league' => 'Nie można usunąć ligi przypisanej do sezonu.']);
        }

        $league->delete();

        return back()->with('status', 'Liga została usunięta.');
    }

    public function attachToSeason(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'season_id' => ['required', 'integer', 'exists:seasons,id'],
            'league_id' => ['required', 'integer', 'exists:leagues,id', Rule::unique('season_leagues', 'league_id')->where(fn ($query) => $query->where('season_id', $request->integer('season_id')))],
            'promotion_places' => ['required', 'integer', 'min:0', 'max:255'],
            'relegation_places' => ['required', 'integer', 'min:0', 'max:255'],
        ]);

        SeasonLeague::create($data);

        return back()->with('status', 'Liga została przypisana do sezonu.');
    }

    public function updateSeasonLeague(Request $request, SeasonLeague $seasonLeague): RedirectResponse
    {
        $data = $request->validate([
            'promotion_places' => ['required', 'integer', 'min:0', 'max:255'],
            'relegation_places' => ['required', 'integer', 'min:0', 'max:255'],
        ]);

        $seasonLeague->update($data);

        return back()->with('status', 'Ustawienia ligi w sezonie zostały zaktualizowane.');
    }

    public function detachFromSeason(SeasonLeague $seasonLeague): RedirectResponse
    {
        if ($seasonLeague->teams()->exists() || $seasonLeague->matches()->exists()) {
            return back()->withErrors(['season_league' => 'Nie można odłączyć ligi, która ma drużyny lub mecze.']);
        }

        $seasonLeague->delete();

        return back()->with('status', 'Liga została odłączona od sezonu.');
    }
}
