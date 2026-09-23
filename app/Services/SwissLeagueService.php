<?php

namespace App\Services;

use App\Models\LeagueRound;
use App\Models\MatchGame;
use App\Models\SeasonLeague;
use App\Models\SeasonTeam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SwissLeagueService
{
    public const MAX_ROUNDS = 9;

    /**
     * Bot-only Swiss rounds have no real Lech match to score against, so once a round's
     * kickoff time passes we simulate a result ourselves instead of leaving it stuck at 0:0.
     */
    public function simulateDueRounds(SeasonLeague $seasonLeague): void
    {
        $seasonLeague->loadMissing('league');

        $round = $seasonLeague->rounds()->with(['matches', 'bonusQuestions'])->orderByDesc('round_number')->first();

        while ($round !== null && $this->hasUnfinishedRound($round) && $round->scheduled_at->isPast()) {
            $this->simulateRound($round);
            $round = $seasonLeague->rounds()->with(['matches', 'bonusQuestions'])->orderByDesc('round_number')->first();
        }
    }

    private function simulateRound(LeagueRound $round): void
    {
        $correctAnswers = $round->bonusQuestions
            ->mapWithKeys(fn ($question): array => [$question->id => (bool) random_int(0, 1)])
            ->all();

        app(LeagueMatchService::class)->completeRound($round, random_int(0, 4), random_int(0, 4), $correctAnswers);
    }

    public function generateNextRound(SeasonLeague $seasonLeague): ?LeagueRound
    {
        return DB::transaction(function () use ($seasonLeague): ?LeagueRound {
            $seasonLeague->loadMissing('league');
            $rounds = $seasonLeague->rounds()->with('matches')->get();
            $nextRoundNumber = $rounds->count() + 1;

            if ($nextRoundNumber > self::MAX_ROUNDS || $this->hasUnfinishedRound($rounds->last())) {
                return null;
            }

            $teams = $this->rankedTeams($seasonLeague);
            if ($teams->count() < 2) {
                return null;
            }

            $round = LeagueRound::create([
                'season_league_id' => $seasonLeague->id,
                'round_number' => $nextRoundNumber,
                'scheduled_at' => now()->addDays($nextRoundNumber - 1),
            ]);
            app(RoundBonusQuestionService::class)->assignRandomQuestions($round);

            foreach ($this->pairTeams($seasonLeague, $teams) as [$home, $away]) {
                MatchGame::create([
                    'season_league_id' => $seasonLeague->id,
                    'league_round_id' => $round->id,
                    'round_number' => $nextRoundNumber,
                    'home_team_id' => $home->team_id,
                    'away_team_id' => $away->team_id,
                    'scheduled_at' => $round->scheduled_at,
                    'status' => 'scheduled',
                ]);
            }

            return $round;
        });
    }

    private function rankedTeams(SeasonLeague $seasonLeague): Collection
    {
        return SeasonTeam::query()
            ->where('season_league_id', $seasonLeague->id)
            ->orderByDesc('points')
            ->orderByDesc(DB::raw('score_for - score_against'))
            ->orderByDesc('score_for')
            ->orderByDesc('wins')
            ->orderByDesc('draws')
            ->orderByDesc('bonus_points')
            ->orderBy('team_id')
            ->get();
    }

    /**
     * @return array<int, array{0: SeasonTeam, 1: SeasonTeam}>
     */
    private function pairTeams(SeasonLeague $seasonLeague, Collection $teams): array
    {
        $remaining = $teams->values();
        $pairs = [];

        while ($remaining->count() > 1) {
            /** @var SeasonTeam $home */
            $home = $remaining->shift();
            $opponentIndex = $remaining->search(
                fn (SeasonTeam $candidate): bool => ! $this->havePlayed($seasonLeague, $home->team_id, $candidate->team_id),
            );

            if ($opponentIndex === false) {
                $opponentIndex = 0;
            }

            /** @var SeasonTeam $away */
            $away = $remaining->pull($opponentIndex);
            $pairs[] = [$home, $away];
        }

        return $pairs;
    }

    private function havePlayed(SeasonLeague $seasonLeague, int $firstTeamId, int $secondTeamId): bool
    {
        return MatchGame::query()
            ->where('season_league_id', $seasonLeague->id)
            ->where(function ($query) use ($firstTeamId, $secondTeamId): void {
                $query
                    ->where(function ($query) use ($firstTeamId, $secondTeamId): void {
                        $query->where('home_team_id', $firstTeamId)->where('away_team_id', $secondTeamId);
                    })
                    ->orWhere(function ($query) use ($firstTeamId, $secondTeamId): void {
                        $query->where('home_team_id', $secondTeamId)->where('away_team_id', $firstTeamId);
                    });
            })
            ->exists();
    }

    private function hasUnfinishedRound(?LeagueRound $round): bool
    {
        return $round !== null && $round->matches->contains(fn (MatchGame $match): bool => $match->status === 'scheduled');
    }
}
