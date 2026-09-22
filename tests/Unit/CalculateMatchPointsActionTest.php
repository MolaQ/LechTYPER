<?php

declare(strict_types=1);

use App\Actions\CalculateMatchPointsAction;
use App\Models\BonusQuestion;
use Illuminate\Support\Collection;

it('calculates cascading base points and risk reward bonuses', function () {
    $questions = collect([
        (new BonusQuestion)->forceFill(['id' => 1, 'type' => 'offensive', 'correct_answer' => true]),
        (new BonusQuestion)->forceFill(['id' => 2, 'type' => 'offensive', 'correct_answer' => false]),
        (new BonusQuestion)->forceFill(['id' => 3, 'type' => 'defensive', 'correct_answer' => true]),
        (new BonusQuestion)->forceFill(['id' => 4, 'type' => 'defensive', 'correct_answer' => true]),
    ]);
    $answers = collect([1 => true, 2 => null, 3 => true, 4 => false]);

    $result = app(CalculateMatchPointsAction::class)->calculate(
        3,
        1,
        2,
        0,
        $questions->where('type', 'offensive'),
        $questions->where('type', 'defensive'),
        $answers,
    );

    expect($result)->toBe(['base' => 2, 'offensive' => 1, 'defensive' => 0, 'total' => 3])
        ->and(app(CalculateMatchPointsAction::class)->applyDefensivePenalty(2, 5))->toBe(0);
});

it('awards no base points when the score is missing', function () {
    $result = app(CalculateMatchPointsAction::class)->calculate(
        null,
        1,
        2,
        0,
        new Collection,
        new Collection,
        new Collection,
    );

    expect($result['base'])->toBe(0);
});
