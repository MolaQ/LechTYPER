<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\LeaguePosition;
use App\Models\LeagueRound;
use App\Models\MatchGame;
use App\Models\Season;
use App\Models\SeasonTeam;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BotLeagueAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $season = Season::query()->where('status', 'active')->firstOrFail();
        $botTeams = Team::query()
            ->whereHas('user', fn ($query) => $query->where('email', 'like', 'bot.%@lechtyper.local'))
            ->orderBy('id')
            ->get();
        $regularLeagues = League::query()->whereBetween('level', [1, 10])->orderBy('level')->get();
        $backyardLeague = League::query()->where('level', 11)->firstOrFail();

        DB::transaction(function () use ($season, $botTeams, $regularLeagues, $backyardLeague): void {
            $botTeamIds = $botTeams->pluck('id');

            SeasonTeam::query()
                ->whereIn('team_id', $botTeamIds)
                ->whereHas('seasonLeague', fn ($query) => $query->where('season_id', $season->id))
                ->delete();

            $assignedBotIds = collect();

            foreach ($regularLeagues as $league) {
                $seasonLeague = $season->seasonLeagues()->where('league_id', $league->id)->firstOrFail();
                $realTeamCount = $seasonLeague->teams()
                    ->whereNotIn('team_id', $botTeamIds)
                    ->count();
                $botSlots = max(0, 10 - $realTeamCount);

                foreach ($botTeams->whereNotIn('id', $assignedBotIds)->take($botSlots) as $botTeam) {
                    SeasonTeam::query()->create([
                        'season_league_id' => $seasonLeague->id,
                        'team_id' => $botTeam->id,
                    ]);
                    $assignedBotIds->push($botTeam->id);
                }

                $this->synchronizeRegularLeague($seasonLeague);
            }

            $backyardSeasonLeague = $season->seasonLeagues()->where('league_id', $backyardLeague->id)->firstOrFail();
            foreach ($botTeams->whereNotIn('id', $assignedBotIds) as $botTeam) {
                SeasonTeam::query()->create([
                    'season_league_id' => $backyardSeasonLeague->id,
                    'team_id' => $botTeam->id,
                ]);
            }
        });
    }

    private function synchronizeRegularLeague($seasonLeague): void
    {
        $hasStartedMatches = $seasonLeague->matches()->where('status', '<>', 'scheduled')->exists()
            || $seasonLeague->matches()->whereHas('selections')->exists();

        if (! $hasStartedMatches) {
            MatchGame::query()->where('season_league_id', $seasonLeague->id)->delete();
            LeagueRound::query()->where('season_league_id', $seasonLeague->id)->delete();
        }

        for ($position = 1; $position <= 10; $position++) {
            LeaguePosition::query()->firstOrCreate(
                ['season_league_id' => $seasonLeague->id, 'position' => $position],
                ['bot_name' => 'Chłopaki z orlika', 'inherited_points' => 0],
            );
        }

        $seasonTeams = $seasonLeague->teams()->orderBy('id')->get();
        foreach ($seasonTeams as $index => $seasonTeam) {
            $positionNumber = $index + 1;
            $seasonTeam->update(['position' => $positionNumber]);
            LeaguePosition::query()
                ->where('season_league_id', $seasonLeague->id)
                ->where('position', $positionNumber)
                ->update(['team_id' => $seasonTeam->team_id]);
        }

        if ($hasStartedMatches || $seasonLeague->matches()->exists()) {
            return;
        }

        $teamIds = $seasonTeams->pluck('team_id')->values()->all();
        $roundTeams = $teamIds;
        $roundCount = count($roundTeams) - 1;

        for ($round = 0; $round < $roundCount; $round++) {
            $leagueRound = LeagueRound::query()->create([
                'season_league_id' => $seasonLeague->id,
                'round_number' => $round + 1,
                'scheduled_at' => now()->addDays($round),
            ]);

            for ($index = 0; $index < intdiv(count($roundTeams), 2); $index++) {
                MatchGame::query()->create([
                    'season_league_id' => $seasonLeague->id,
                    'league_round_id' => $leagueRound->id,
                    'round_number' => $round + 1,
                    'home_team_id' => $roundTeams[$index],
                    'away_team_id' => $roundTeams[count($roundTeams) - 1 - $index],
                    'scheduled_at' => $leagueRound->scheduled_at,
                    'status' => 'scheduled',
                ]);
            }

            $lastTeamId = array_pop($roundTeams);
            array_splice($roundTeams, 1, 0, [$lastTeamId]);
        }
    }
}
