<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\BonusQuestion;
use App\Models\H2hFixture;
use App\Models\Prediction;
use App\Models\UserAnswer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalculateMatchPointsAction
{
    /**
     * @return array{base: int, offensive: int, defensive: int, total: int}
     */
    public function calculate(
        ?int $predictedHome,
        ?int $predictedAway,
        int $actualHome,
        int $actualAway,
        Collection $offensiveQuestions,
        Collection $defensiveQuestions,
        Collection $answers,
    ): array {
        $base = $this->calculateBasePoints($predictedHome, $predictedAway, $actualHome, $actualAway);
        $offensive = $this->calculateBonusPoints($offensiveQuestions, $answers);
        $defensive = $this->calculateBonusPoints($defensiveQuestions, $answers);

        return [
            'base' => $base,
            'offensive' => $offensive,
            'defensive' => $defensive,
            'total' => $base + $offensive,
        ];
    }

    public function applyDefensivePenalty(int $opponentPoints, int $defensivePoints): int
    {
        return max(0, $opponentPoints - $defensivePoints);
    }

    public function calculatePrediction(Prediction $prediction, Collection $answers): array
    {
        $match = $prediction->match()->with(['bonusQuestions'])->firstOrFail();
        $actualHome = (int) $match->result_home;
        $actualAway = (int) $match->result_away;

        return $this->calculate(
            $prediction->home_score,
            $prediction->away_score,
            $actualHome,
            $actualAway,
            $match->bonusQuestions->where('type', 'offensive'),
            $match->bonusQuestions->where('type', 'defensive'),
            $answers,
        );
    }

    public function settleFixture(H2hFixture $fixture): void
    {
        DB::transaction(function () use ($fixture): void {
            $fixture->loadMissing('match.bonusQuestions');
            $predictions = Prediction::query()
                ->where('match_id', $fixture->match_id)
                ->whereIn('user_id', [$fixture->user_id, $fixture->opponent_id])
                ->get()
                ->keyBy('user_id');

            $userPrediction = $predictions->get($fixture->user_id);
            $opponentPrediction = $predictions->get($fixture->opponent_id);

            if ($userPrediction === null || $opponentPrediction === null) {
                return;
            }

            $userAnswers = $this->answersFor($fixture->user_id, $fixture->match_id);
            $opponentAnswers = $this->answersFor($fixture->opponent_id, $fixture->match_id);
            $userPoints = $this->calculatePrediction($userPrediction, $userAnswers);
            $opponentPoints = $this->calculatePrediction($opponentPrediction, $opponentAnswers);

            $userTotal = $userPoints['total'];
            $opponentTotal = $opponentPoints['total'];
            $userDefence = $userPoints['defensive'];
            $opponentDefence = $opponentPoints['defensive'];

            $userPrediction->update([
                'points_base' => $userPoints['base'],
                'points_offensive' => $userPoints['offensive'],
                'points_defensive_applied' => $opponentDefence,
                'total_points' => $userTotal,
            ]);
            $opponentPrediction->update([
                'points_base' => $opponentPoints['base'],
                'points_offensive' => $opponentPoints['offensive'],
                'points_defensive_applied' => $userDefence,
                'total_points' => $this->applyDefensivePenalty($opponentTotal, $userDefence),
            ]);
        });
    }

    private function calculateBasePoints(?int $predictedHome, ?int $predictedAway, int $actualHome, int $actualAway): int
    {
        if ($predictedHome === null || $predictedAway === null) {
            return 0;
        }

        $points = $this->outcome($predictedHome, $predictedAway) === $this->outcome($actualHome, $actualAway) ? 1 : 0;
        $points += $predictedHome - $predictedAway === $actualHome - $actualAway ? 1 : 0;
        $points += $predictedHome === $actualHome && $predictedAway === $actualAway ? 1 : 0;

        return $points;
    }

    private function calculateBonusPoints(Collection $questions, Collection $answers): int
    {
        $answered = $questions->mapWithKeys(function (BonusQuestion $question) use ($answers): array {
            return [$question->id => $answers->get($question->id)];
        })->filter(fn (?bool $answer): bool => $answer !== null);

        $hasWrongAnswer = $answered->contains(function (bool $answer, int $questionId) use ($questions): bool {
            return (bool) $questions->firstWhere('id', $questionId)?->correct_answer !== $answer;
        });

        if ($hasWrongAnswer) {
            return 0;
        }

        return $answered->filter(fn (?bool $answer): bool => $answer === true)->filter(
            fn (bool $answer, int $questionId): bool => (bool) $questions->firstWhere('id', $questionId)?->correct_answer === $answer,
        )->count();
    }

    private function answersFor(int $userId, int $matchId): Collection
    {
        return UserAnswer::query()
            ->where('user_id', $userId)
            ->whereHas('question', fn ($query) => $query->where('match_id', $matchId))
            ->pluck('answer', 'bonus_question_id')
            ->map(fn ($answer): ?bool => $answer === null ? null : (bool) $answer);
    }

    private function outcome(int $home, int $away): int
    {
        return $home <=> $away;
    }
}
