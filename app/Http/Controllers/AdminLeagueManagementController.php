<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueRound;
use App\Models\MatchGame;
use App\Models\Season;
use App\Models\SeasonLeague;
use App\Models\SeasonRound;
use App\Models\SeasonTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminLeagueManagementController extends Controller
{
    private const COMPETITIONS = [
        'Ekstraklasa',
        'Puchar Polski',
        'Mecz towarzyski',
        'Liga Mistrzów',
        'Liga Europy',
        'Liga Konferencji',
    ];

    public function index(Request $request): View
    {
        $seasons = Season::query()->with('seasonLeagues.league')->orderByDesc('starts_at')->get();
        $season = $seasons->firstWhere('id', $request->integer('season_id'))
            ?? $seasons->firstWhere('status', 'active')
            ?? $seasons->first();
        abort_if($season === null, 404, 'Brak skonfigurowanych sezonów.');
        $this->ensureSeasonRounds($season);

        $seasonLeagues = $season->seasonLeagues->sortBy('league.level')->values();
        $selectedSeasonLeague = $seasonLeagues->firstWhere('league.slug', $request->string('league')->toString())
            ?? $seasonLeagues->firstWhere('league.slug', 'ekstraklasa')
            ?? $seasonLeagues->first();
        abort_if($selectedSeasonLeague === null, 404, 'Wybrany sezon nie ma przypisanych lig.');

        return view('admin.leagues.index', [
            'leagues' => League::query()->withCount('seasonLeagues')->orderBy('level')->get(),
            'seasons' => $seasons,
            'season' => $season,
            'seasonLeagues' => $seasonLeagues,
            'selectedSeasonLeague' => $selectedSeasonLeague,
            'selectedTeams' => $selectedSeasonLeague->teams()->with(['team.user', 'seasonLeague.season'])->get(),
            'selectedRounds' => $selectedSeasonLeague->rounds()->with('matches.homeTeam', 'matches.awayTeam')->get(),
            'selectedMatches' => $selectedSeasonLeague->matches()->with(['homeTeam', 'awayTeam'])->orderBy('scheduled_at')->get(),
            'roundCount' => $this->roundCount($season, $selectedSeasonLeague),
            'users' => User::query()->orderBy('name')->get(),
            'seasonTeams' => SeasonTeam::query()->with(['seasonLeague.season', 'seasonLeague.league', 'team.user'])->get(),
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

    public function addTeamToLeague(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'season_league_id' => ['required', 'integer', 'exists:season_leagues,id'],
        ]);

        $user = User::query()->findOrFail($data['user_id']);
        $team = Team::firstOrCreate(['user_id' => $user->id], ['name' => $user->name]);

        if (SeasonTeam::query()->where('season_league_id', $data['season_league_id'])->where('team_id', $team->id)->exists()) {
            return back()->withErrors(['team' => 'Ta drużyna jest już przypisana do wybranej ligi.']);
        }

        SeasonTeam::create([
            'season_league_id' => $data['season_league_id'],
            'team_id' => $team->id,
        ]);

        return back()->with('status', "Drużyna {$team->name} została dodana do ligi.");
    }

    public function removeTeamFromLeague(SeasonTeam $seasonTeam, DatabaseManager $database): RedirectResponse
    {
        $seasonTeam->load(['seasonLeague.season', 'seasonLeague.league', 'team.user']);
        $isBackyardLeague = $seasonTeam->seasonLeague->league->level === 11;

        if ($isBackyardLeague && $seasonTeam->seasonLeague->season->ends_at->isFuture()) {
            return back()->withErrors(['team' => 'Drużynę z ligi podwórkowej można usunąć dopiero po zakończeniu sezonu.']);
        }

        if ($isBackyardLeague) {
            $team = $seasonTeam->team;
            $teamUser = $team->user;
            $database->transaction(function () use ($seasonTeam, $teamUser): void {
                $seasonTeam->team->delete();
                $teamUser->delete();
            });

            return back()->with('status', 'Drużyna i konto zostały usunięte po zakończeniu sezonu.');
        }

        $seasonTeam->delete();

        return back()->with('status', 'Drużyna została usunięta z ligi.');
    }

    public function createSeason(Request $request, DatabaseManager $database): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'rounds' => ['required', 'integer', 'min:1', 'max:255'],
        ]);

        $database->transaction(function () use ($data): void {
            $previousSeason = Season::query()->where('status', 'active')->with('seasonLeagues.league')->first();
            $newSeason = Season::create($data + ['status' => 'planned']);

            for ($round = 1; $round <= (int) $data['rounds']; $round++) {
                SeasonRound::create(['season_id' => $newSeason->id, 'round_number' => $round]);
            }

            if ($previousSeason === null) {
                return;
            }

            $previousBackyard = $previousSeason->seasonLeagues->first(fn ($seasonLeague) => $seasonLeague->league->level === 11);

            foreach ($previousSeason->seasonLeagues as $previousSeasonLeague) {
                $newSeasonLeague = SeasonLeague::create([
                    'season_id' => $newSeason->id,
                    'league_id' => $previousSeasonLeague->league_id,
                    'promotion_places' => $previousSeasonLeague->promotion_places,
                    'relegation_places' => $previousSeasonLeague->relegation_places,
                ]);

                if ($previousBackyard?->id !== $previousSeasonLeague->id) {
                    continue;
                }

                foreach ($previousBackyard->teams()->with('seasonLeague')->get() as $seasonTeam) {
                    if (! $seasonTeam->hasSubmittedTypeInSeason()) {
                        $team = $seasonTeam->team;
                        $teamUser = $team->user;
                        $team->delete();
                        $teamUser->delete();

                        continue;
                    }

                    $newSeasonLeague->teams()->create(['team_id' => $seasonTeam->team_id]);
                }
            }

            $previousSeason?->update(['status' => 'completed']);
        });

        return back()->with('status', 'Nowy sezon został utworzony, a liga podwórkowa została oczyszczona według aktywności typowania.');
    }

    public function generateSchedule(Request $request, DatabaseManager $database): RedirectResponse
    {
        $data = $request->validate([
            'season_league_id' => ['required', 'integer', 'exists:season_leagues,id'],
        ]);
        $seasonLeague = SeasonLeague::query()->with('teams')->findOrFail($data['season_league_id']);

        if ($seasonLeague->rounds()->exists() || $seasonLeague->matches()->exists()) {
            return back()->withErrors(['schedule' => 'Terminarz tej ligi został już wygenerowany.']);
        }

        $teamIds = $seasonLeague->teams()->orderBy('id')->pluck('team_id')->all();
        $roundCount = (int) $seasonLeague->season->getRawOriginal('rounds');

        $database->transaction(function () use ($seasonLeague, $teamIds, $roundCount): void {
            if (count($teamIds) % 2 !== 0) {
                $teamIds[] = null;
            }
            $teamCount = count($teamIds);
            $half = intdiv($teamCount, 2);

            for ($round = 0; $round < $roundCount; $round++) {
                $leagueRound = LeagueRound::create([
                    'season_league_id' => $seasonLeague->id,
                    'round_number' => $round + 1,
                    'scheduled_at' => now(),
                ]);
                for ($index = 0; $index < $half; $index++) {
                    $homeTeamId = $teamIds[$index];
                    $awayTeamId = $teamIds[$teamCount - 1 - $index];
                    if ($homeTeamId === null || $awayTeamId === null) {
                        continue;
                    }

                    MatchGame::create([
                        'season_league_id' => $seasonLeague->id,
                        'league_round_id' => $leagueRound->id,
                        'round_number' => $round + 1,
                        'home_team_id' => $homeTeamId,
                        'away_team_id' => $awayTeamId,
                        'scheduled_at' => now(),
                        'status' => 'scheduled',
                    ]);
                }

                $lastTeamId = array_pop($teamIds);
                array_splice($teamIds, 1, 0, [$lastTeamId]);
            }
        });

        return back()->with('status', 'Terminarz jednej rundy został wygenerowany.');
    }

    public function updateRound(Request $request, LeagueRound $round): RedirectResponse
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'real_match_at' => ['nullable', 'date'],
            'real_home_team' => ['required', 'string', 'max:120'],
            'real_away_team' => ['required', 'string', 'max:120'],
            'competition' => ['required', Rule::in(self::COMPETITIONS)],
        ]);
        $round->update($data);

        return back()->with('status', "Kolejka {$round->round_number} została zaktualizowana.");
    }

    private function roundCount(Season $season, SeasonLeague $seasonLeague): int
    {
        $teamCount = $seasonLeague->teams()->count();

        return $teamCount > 1
            ? ($teamCount % 2 === 0 ? $teamCount - 1 : $teamCount)
            : max(1, (int) $season->getRawOriginal('rounds'));
    }

    public function schedule(Request $request): View
    {
        $seasons = Season::query()->orderByDesc('starts_at')->get();
        $season = $seasons->firstWhere('id', $request->integer('season_id'))
            ?? $seasons->firstWhere('status', 'active')
            ?? $seasons->first();
        abort_if($season === null, 404, 'Brak skonfigurowanych sezonów.');
        $this->ensureSeasonRounds($season);

        return view('admin.leagues.schedule', compact('seasons', 'season'));
    }

    public function updateSeasonRound(Request $request, SeasonRound $round): RedirectResponse
    {
        $data = $request->validate([
            'real_match_at' => ['nullable', 'date'],
            'real_home_team' => ['required', 'string', 'max:120'],
            'real_away_team' => ['required', 'string', 'max:120'],
            'competition' => ['required', Rule::in(self::COMPETITIONS)],
        ]);
        $round->update($data + ['scheduled_at' => $data['real_match_at']]);

        return back()->with('status', "Kolejka {$round->round_number} została zaktualizowana.");
    }

    private function ensureSeasonRounds(Season $season): void
    {
        if ($season->seasonRounds()->exists()) {
            return;
        }

        for ($round = 1; $round <= max(1, (int) $season->getRawOriginal('rounds')); $round++) {
            SeasonRound::create(['season_id' => $season->id, 'round_number' => $round]);
        }
    }
}
