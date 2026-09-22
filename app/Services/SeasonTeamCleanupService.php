<?php

namespace App\Services;

use App\Models\SeasonLeague;
use App\Models\SeasonTeam;

class SeasonTeamCleanupService
{
    /**
     * Removes stale season-team rows left behind after a bot was replaced by a real team,
     * and backfills bonus questions for rounds generated before that feature existed.
     */
    public function reconcile(SeasonLeague $seasonLeague): void
    {
        $seasonLeague->loadMissing('league');

        if ($seasonLeague->league->level !== 11) {
            $activeTeamIds = $seasonLeague->positions()->whereNotNull('team_id')->pluck('team_id');
            SeasonTeam::query()
                ->where('season_league_id', $seasonLeague->id)
                ->whereNotIn('team_id', $activeTeamIds)
                ->delete();
        }

        foreach ($seasonLeague->rounds()->get() as $round) {
            app(RoundBonusQuestionService::class)->assignRandomQuestions($round);
        }
    }
}
