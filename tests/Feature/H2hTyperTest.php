<?php

declare(strict_types=1);

use App\Actions\CalculateMatchPointsAction;
use App\Models\BonusQuestion;
use App\Models\Competition;
use App\Models\H2hFixture;
use App\Models\LechMatch;
use App\Models\Prediction;
use App\Models\User;
use App\Models\UserAnswer;

it('applies defensive points to the opponent in an H2H fixture', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $competition = Competition::create(['name' => 'Test Liga', 'slug' => 'test-liga']);
    $match = LechMatch::create([
        'competition_id' => $competition->id,
        'opponent' => 'Test Rywal',
        'scheduled_at' => now()->subHour(),
        'result_home' => 2,
        'result_away' => 0,
        'status' => 'completed',
    ]);
    $defensiveQuestion = BonusQuestion::create([
        'match_id' => $match->id,
        'type' => 'defensive',
        'question_text' => 'Czy Lech zachowa czyste konto?',
        'correct_answer' => true,
    ]);
    $fixture = H2hFixture::create([
        'user_id' => $firstUser->id,
        'opponent_id' => $secondUser->id,
        'match_id' => $match->id,
    ]);
    $firstPrediction = Prediction::create(['user_id' => $firstUser->id, 'match_id' => $match->id, 'home_score' => 2, 'away_score' => 0]);
    $secondPrediction = Prediction::create(['user_id' => $secondUser->id, 'match_id' => $match->id, 'home_score' => 0, 'away_score' => 1]);
    UserAnswer::create(['user_id' => $firstUser->id, 'bonus_question_id' => $defensiveQuestion->id, 'answer' => true]);

    app(CalculateMatchPointsAction::class)->settleFixture($fixture);

    expect($firstPrediction->fresh()->total_points)->toBe(3)
        ->and($secondPrediction->fresh()->total_points)->toBe(0)
        ->and($secondPrediction->fresh()->points_defensive_applied)->toBe(1);
});
