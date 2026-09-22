<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeaguePosition;
use App\Models\LeagueRound;
use App\Models\LechMatch;
use App\Models\MatchGame;
use App\Models\RealMatch;
use App\Models\Season;
use App\Models\SeasonLeague;
use App\Models\SeasonRound;
use App\Models\SeasonTeam;
use App\Models\Team;
use App\Models\User;
use App\Services\SwissLeagueService;
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
        $this->ensureSeasonLeagueSchedules($season);

        $seasonLeagues = $season->seasonLeagues->sortBy('league.level')->values();
        $selectedSeasonLeague = $seasonLeagues->firstWhere('league.slug', $request->string('league')->toString())
            ?? $seasonLeagues->firstWhere('league.slug', 'ekstraklasa')
            ?? $seasonLeagues->first();
        abort_if($selectedSeasonLeague === null, 404, 'Wybrany sezon nie ma przypisanych lig.');
        $this->ensureLeaguePositions($selectedSeasonLeague);

        return view('admin.leagues.index', [
            'leagues' => League::query()->withCount('seasonLeagues')->orderBy('level')->get(),
            'seasons' => $seasons,
            'season' => $season,
            'seasonLeagues' => $seasonLeagues,
            'selectedSeasonLeague' => $selectedSeasonLeague,
            'selectedTeams' => $selectedSeasonLeague->teams()->with(['team.user', 'seasonLeague.season'])->get(),
            'positions' => $selectedSeasonLeague->positions()->with('team')->get(),
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
            'position' => ['required', 'integer', 'between:1,10'],
        ]);

        $seasonLeague = SeasonLeague::query()->findOrFail($data['season_league_id']);
        $this->ensureLeaguePositions($seasonLeague);
        $position = LeaguePosition::query()->where('season_league_id', $seasonLeague->id)->where('position', $data['position'])->firstOrFail();
        $isBotPosition = $this->isBotPosition($position);
        if ($position->team_id !== null && ! $isBotPosition) {
            return back()->withErrors(['position' => 'Wybrana pozycja jest już zajęta przez realną drużynę.']);
        }

        $user = User::query()->findOrFail($data['user_id']);
        $team = Team::firstOrCreate(['user_id' => $user->id], ['name' => $user->name]);
        $botTeamId = $isBotPosition ? $position->team_id : null;

        $existingSeasonTeam = SeasonTeam::query()
            ->where('season_league_id', $seasonLeague->id)
            ->where('team_id', $team->id)
            ->first();

        $alreadyInAnotherLeague = SeasonTeam::query()
            ->where('team_id', $team->id)
            ->where('season_league_id', '<>', $seasonLeague->id)
            ->whereHas('seasonLeague', fn ($query) => $query->where('season_id', $seasonLeague->season_id))
            ->exists();

        if ($alreadyInAnotherLeague) {
            $backyardAssignment = SeasonTeam::query()
                ->where('team_id', $team->id)
                ->where('season_league_id', '<>', $seasonLeague->id)
                ->whereHas('seasonLeague.league', fn ($query) => $query->where('level', 11))
                ->first();

            if ($backyardAssignment) {
                LeaguePosition::query()
                    ->where('season_league_id', $backyardAssignment->season_league_id)
                    ->where('team_id', $team->id)
                    ->update(['team_id' => null]);
                $backyardAssignment->delete();
                $alreadyInAnotherLeague = false;
            }
        }

        if ($alreadyInAnotherLeague) {
            return back()->withErrors(['team' => 'Ta drużyna jest już przypisana do innej ligi w tym sezonie.']);
        }

        if ($existingSeasonTeam) {
            LeaguePosition::query()->where('season_league_id', $seasonLeague->id)->where('team_id', $team->id)->update(['team_id' => null]);
            $existingSeasonTeam->update(['position' => $position->position]);
            $position->update(['team_id' => $team->id]);
            $this->replaceTeamInSchedule($seasonLeague, $botTeamId, $team->id);

            return back()->with('status', "Drużyna {$team->name} została przypisana do pozycji {$position->position}.");
        }

        SeasonTeam::create([
            'season_league_id' => $seasonLeague->id,
            'team_id' => $team->id,
            'position' => $position->position,
        ]);
        $position->update(['team_id' => $team->id]);
        $this->replaceTeamInSchedule($seasonLeague, $botTeamId, $team->id);

        return back()->with('status', "Drużyna {$team->name} została dodana do ligi.");
    }

    private function replaceTeamInSchedule(SeasonLeague $seasonLeague, ?int $oldTeamId, int $newTeamId): void
    {
        if ($oldTeamId === null || $oldTeamId === $newTeamId) {
            return;
        }

        MatchGame::query()
            ->where('season_league_id', $seasonLeague->id)
            ->where('home_team_id', $oldTeamId)
            ->update(['home_team_id' => $newTeamId]);

        MatchGame::query()
            ->where('season_league_id', $seasonLeague->id)
            ->where('away_team_id', $oldTeamId)
            ->update(['away_team_id' => $newTeamId]);
    }

    private function isBotPosition(LeaguePosition $position): bool
    {
        $team = $position->team()->with('user')->first();
        if ($team === null || $team->user === null) {
            return false;
        }

        $email = strtolower((string) $team->user->email);
        $name = strtolower((string) $team->name);

        return str_starts_with($email, 'bot.')
            || str_contains($email, 'bot.')
            || str_contains($name, 'chłopaki z orlika')
            || str_contains($name, 'chlopaki z orlika');
    }

    private function ensureLeaguePositions(SeasonLeague $seasonLeague): void
    {
        for ($position = 1; $position <= 10; $position++) {
            LeaguePosition::firstOrCreate(
                ['season_league_id' => $seasonLeague->id, 'position' => $position],
                ['bot_name' => 'Chłopaki z orlika', 'inherited_points' => 0],
            );
        }

        foreach ($seasonLeague->teams()->whereNull('position')->orderBy('id')->get() as $seasonTeam) {
            $freePosition = LeaguePosition::query()
                ->where('season_league_id', $seasonLeague->id)
                ->whereNull('team_id')
                ->orderBy('position')
                ->first();
            if ($freePosition === null) {
                break;
            }

            $freePosition->update(['team_id' => $seasonTeam->team_id]);
            $seasonTeam->update(['position' => $freePosition->position]);
        }
    }

    private function ensureBotPosition(LeaguePosition $position, SeasonLeague $seasonLeague): void
    {
        if ($position->team_id !== null) {
            return;
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

        $database->transaction(function () use ($data, $database): void {
            $previousSeason = Season::query()->where('status', 'active')->with('seasonLeagues.league')->first();
            $newSeason = Season::create($data + ['status' => 'planned']);

            for ($round = 1; $round <= (int) $data['rounds']; $round++) {
                SeasonRound::create(['season_id' => $newSeason->id, 'round_number' => $round]);
            }

            if ($previousSeason === null) {
                foreach (SeasonLeague::query()->where('season_id', $newSeason->id)->get() as $newSeasonLeague) {
                    $this->generateLeagueMatches($newSeasonLeague, $database);
                }

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
                    $this->generateLeagueMatches($newSeasonLeague, $database);

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

                $this->generateLeagueMatches($newSeasonLeague, $database);
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

        $this->generateLeagueMatches($seasonLeague, $database);

        return back()->with('status', 'Terminarz ligi został wygenerowany automatycznie.');
    }

    private function generateLeagueMatches(SeasonLeague $seasonLeague, DatabaseManager $database): void
    {
        $seasonLeague->loadMissing('league');

        if ($seasonLeague->league->level === 11) {
            app(SwissLeagueService::class)->generateNextRound($seasonLeague);

            return;
        }

        if ($seasonLeague->rounds()->exists() || $seasonLeague->matches()->exists()) {
            return;
        }

        $database->transaction(function () use ($seasonLeague): void {
            $this->ensureLeaguePositions($seasonLeague);
            $positions = $seasonLeague->positions()->orderBy('position')->get();

            foreach ($positions as $position) {
                $this->ensureBotPosition($position, $seasonLeague);
            }

            $teamIds = $positions->pluck('team_id')->filter()->values()->all();
            if ($teamIds === []) {
                return;
            }

            $roundCount = count($teamIds) - 1;
            if (count($teamIds) % 2 !== 0) {
                $teamIds[] = null;
                $roundCount = count($teamIds) - 1;
            }

            $roundTeams = $teamIds;
            for ($round = 0; $round < $roundCount; $round++) {
                $leagueRound = LeagueRound::create([
                    'season_league_id' => $seasonLeague->id,
                    'round_number' => $round + 1,
                    'scheduled_at' => now()->addDays($round),
                ]);

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
        });
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
        $this->ensureSeasonLeagueSchedules($season);

        return view('admin.leagues.schedule', [
            'seasons' => $seasons,
            'season' => $season,
            'rounds' => $season->seasonRounds()->with('realMatch')->get(),
            'realMatches' => $season->realMatches()->with('seasonRound')->get(),
            'typerMatches' => LechMatch::query()->with('competition')->withCount('predictions')->orderByDesc('scheduled_at')->get(),
        ]);
    }

    public function storeRealMatch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'season_id' => ['required', 'integer', 'exists:seasons,id'],
            'scheduled_at' => ['required', 'date'],
            'home_team' => ['required', 'string', 'max:120'],
            'away_team' => ['required', 'string', 'max:120'],
            'competition' => ['required', Rule::in(self::COMPETITIONS)],
            'season_round_id' => ['nullable', 'integer', 'exists:season_rounds,id'],
        ]);

        if (! empty($data['season_round_id']) && RealMatch::query()->where('season_round_id', $data['season_round_id'])->exists()) {
            return back()->withErrors(['season_round_id' => 'Ta kolejka ma już przypisany mecz rzeczywisty.']);
        }

        if (! empty($data['season_round_id']) && ! SeasonRound::query()->where('id', $data['season_round_id'])->where('season_id', $data['season_id'])->exists()) {
            return back()->withErrors(['season_round_id' => 'Wybrana kolejka nie należy do tego sezonu.']);
        }

        RealMatch::create($data);

        return back()->with('status', 'Mecz rzeczywisty został dodany.');
    }

    public function assignRealMatch(Request $request, RealMatch $realMatch): RedirectResponse
    {
        $data = $request->validate([
            'season_round_id' => ['required', 'integer', 'exists:season_rounds,id'],
        ]);

        $round = SeasonRound::query()->where('season_id', $realMatch->season_id)->findOrFail($data['season_round_id']);
        if ($round->realMatch()->where('id', '<>', $realMatch->id)->exists()) {
            return back()->withErrors(['season_round_id' => 'Ta kolejka ma już przypisany mecz rzeczywisty.']);
        }

        $realMatch->update(['season_round_id' => $round->id]);

        return back()->with('status', 'Mecz Lecha został przypisany do kolejki.');
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

    private function ensureSeasonLeagueSchedules(Season $season): void
    {
        foreach ($season->seasonLeagues()->get() as $seasonLeague) {
            $this->cleanupDuplicateSeasonTeams($seasonLeague);

            if ($seasonLeague->rounds()->exists() || $seasonLeague->matches()->exists()) {
                continue;
            }

            $this->generateLeagueMatches($seasonLeague, app(DatabaseManager::class));
        }
    }

    private function cleanupDuplicateSeasonTeams(SeasonLeague $seasonLeague): void
    {
        $teamIds = $seasonLeague->teams()->pluck('team_id');
        if ($teamIds->isEmpty()) {
            return;
        }

        $duplicateTeamIds = SeasonTeam::query()
            ->whereIn('team_id', $teamIds)
            ->whereHas('seasonLeague', fn ($query) => $query->where('season_id', $seasonLeague->season_id))
            ->select(['id', 'season_league_id', 'team_id'])
            ->get()
            ->groupBy('team_id')
            ->filter(fn ($rows) => $rows->count() > 1)
            ->keys();

        foreach ($duplicateTeamIds as $teamId) {
            $rows = SeasonTeam::query()
                ->where('team_id', $teamId)
                ->whereHas('seasonLeague', fn ($query) => $query->where('season_id', $seasonLeague->season_id))
                ->orderBy('id')
                ->get();

            $rows->slice(1)->each(fn (SeasonTeam $seasonTeam) => $seasonTeam->delete());
        }
    }
}
