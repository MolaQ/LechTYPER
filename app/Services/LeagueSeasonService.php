<?php

namespace App\Services;

use App\Models\Season;
use Illuminate\Support\Facades\DB;

class LeagueSeasonService
{
    public function complete(Season $season): void
    {
        DB::transaction(function () use ($season): void {
            $season->load('seasonLeagues.league');
            $seasonLeaguesByLevel = $season->seasonLeagues->keyBy(fn ($seasonLeague) => $seasonLeague->league->level);
            $moves = [];

            foreach ($season->seasonLeagues as $seasonLeague) {
                $standings = $seasonLeague->teams()->orderByDesc('points')->orderByDesc(DB::raw('score_for - score_against'))->orderBy('id')->get();
                foreach ($standings as $position => $standing) {
                    $standing->update(['position' => $position + 1]);
                }

                $level = $seasonLeague->league->level;
                if ($level > 1) {
                    foreach ($standings->take(4) as $standing) {
                        $moves[$standing->team_id] = $seasonLeaguesByLevel[$level - 1]->id;
                    }
                }
                if ($level < 11) {
                    foreach ($standings->slice(-4) as $standing) {
                        $moves[$standing->team_id] = $seasonLeaguesByLevel[$level + 1]->id;
                    }
                }
            }

            foreach ($moves as $teamId => $seasonLeagueId) {
                SeasonTeam::where('team_id', $teamId)
                    ->whereHas('seasonLeague', fn ($query) => $query->where('season_id', $season->id))
                    ->update(['season_league_id' => $seasonLeagueId]);
            }

            $season->update(['status' => 'completed']);
        });
    }
}
