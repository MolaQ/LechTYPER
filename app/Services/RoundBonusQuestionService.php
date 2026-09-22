<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BonusQuestionPool;
use App\Models\LeagueRound;

class RoundBonusQuestionService
{
    private const QUESTIONS_PER_TYPE = 5;

    private const TYPES = ['offensive', 'defensive'];

    public function assignRandomQuestions(LeagueRound $round): void
    {
        if ($round->bonusQuestions()->exists()) {
            return;
        }

        $usedIds = collect();

        foreach (self::TYPES as $type) {
            $questions = BonusQuestionPool::query()
                ->where('is_active', true)
                ->whereNotIn('id', $usedIds->values()->all())
                ->inRandomOrder()
                ->limit(self::QUESTIONS_PER_TYPE)
                ->get();

            foreach ($questions as $question) {
                $round->bonusQuestions()->create([
                    'pool_question_id' => $question->id,
                    'type' => $type,
                    'question_text' => $question->question_text,
                ]);
                $usedIds->push($question->id);
            }
        }
    }
}
