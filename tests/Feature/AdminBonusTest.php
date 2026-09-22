<?php

declare(strict_types=1);

use App\Models\BonusQuestionPool;
use App\Models\Competition;
use App\Models\LechMatch;
use App\Models\User;

it('lets an admin assign and draw only missing bonus questions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $competition = Competition::create(['name' => 'Bonus Liga', 'slug' => 'bonus-liga']);
    $match = LechMatch::create([
        'competition_id' => $competition->id,
        'opponent' => 'Bonus Rywal',
        'scheduled_at' => now()->addDay(),
        'status' => 'scheduled',
    ]);

    $offensive = collect(range(1, 5))->map(fn (int $number) => BonusQuestionPool::create(['type' => 'offensive', 'question_text' => "Ofensywne {$number}"]));
    $defensive = collect(range(1, 5))->map(fn (int $number) => BonusQuestionPool::create(['type' => 'defensive', 'question_text' => "Defensywne {$number}"]));

    $this->actingAs($admin)->post(route('admin.bonuses.assign', $match), [
        'offensive' => [$offensive->first()->id, null, null, null, null],
        'defensive' => [$defensive->first()->id, null, null, null, null],
    ])->assertRedirect();

    expect($match->bonusQuestions()->count())->toBe(2);
    $this->actingAs($admin)->post(route('admin.bonuses.draw', $match))->assertRedirect();
    expect($match->fresh()->bonusQuestions()->count())->toBe(10)
        ->and($match->bonusQuestions()->pluck('pool_question_id')->unique())->toHaveCount(10)
        ->and($match->bonusQuestions()->where('type', 'offensive')->count())->toBe(5)
        ->and($match->bonusQuestions()->where('type', 'defensive')->count())->toBe(5);
});

it('rejects the same pool question in both bonus categories', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $competition = Competition::create(['name' => 'Unikalna Liga', 'slug' => 'unikalna-liga']);
    $match = LechMatch::create(['competition_id' => $competition->id, 'opponent' => 'Rywal', 'scheduled_at' => now()->addDay()]);
    $question = BonusQuestionPool::create(['type' => 'offensive', 'question_text' => 'Jedno pytanie']);

    $this->actingAs($admin)->post(route('admin.bonuses.assign', $match), [
        'offensive' => [$question->id, null, null, null, null],
        'defensive' => [$question->id, null, null, null, null],
    ])->assertSessionHasErrors('questions');
});
