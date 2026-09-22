<?php

declare(strict_types=1);

use App\Models\BonusQuestion;
use App\Models\Competition;
use App\Models\LechMatch;
use App\Models\Prediction;
use App\Models\User;
use App\Models\UserAnswer;

it('assigns a Lech match to a specific round and shows it in the admin list', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $competition = Competition::create(['name' => 'Kolejka Liga', 'slug' => 'kolejka-liga']);

    $this->actingAs($admin)->post(route('admin.typer.matches.store'), [
        'competition_id' => $competition->id,
        'round_number' => 5,
        'opponent' => 'Rywal Kolejkowy',
        'lech_home' => 1,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i'),
    ])->assertRedirect();

    $match = LechMatch::query()->where('opponent', 'Rywal Kolejkowy')->firstOrFail();
    expect($match->round_number)->toBe(5);

    $this->actingAs($admin)->get(route('admin.typer.index'))->assertOk()->assertSee('Kolejka 5');
});

it('allows an admin to edit a match and reassign it to a new round', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $competition = Competition::create(['name' => 'Edycja Liga', 'slug' => 'edycja-liga']);
    $match = LechMatch::create([
        'competition_id' => $competition->id,
        'round_number' => 2,
        'opponent' => 'Rywal Przed Edycją',
        'scheduled_at' => now()->addDay(),
    ]);

    $this->actingAs($admin)->patch(route('admin.typer.matches.update', $match), [
        'competition_id' => $competition->id,
        'round_number' => 7,
        'opponent' => 'Rywal Po Edycji',
        'lech_home' => 1,
        'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i'),
    ])->assertRedirect();

    expect($match->fresh()->round_number)->toBe(7)
        ->and($match->fresh()->opponent)->toBe('Rywal Po Edycji');
});

it('lets an admin preview every prediction submitted for a match', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['name' => 'Kibic Typer']);
    $competition = Competition::create(['name' => 'Podglad Liga', 'slug' => 'podglad-liga']);
    $match = LechMatch::create([
        'competition_id' => $competition->id,
        'opponent' => 'Rywal Podgladu',
        'scheduled_at' => now()->addDay(),
    ]);
    Prediction::create([
        'user_id' => $user->id,
        'match_id' => $match->id,
        'home_score' => 3,
        'away_score' => 1,
        'total_points' => 2,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.typer.matches.predictions', $match))
        ->assertOk()
        ->assertSee('Kibic Typer')
        ->assertSee('3:1');
});

it('links the admin schedule to submitted prediction preview', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $competition = Competition::create(['name' => 'Terminarz Liga', 'slug' => 'terminarz-liga']);
    $match = LechMatch::create([
        'competition_id' => $competition->id,
        'opponent' => 'Rywal Terminarza',
        'scheduled_at' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.schedule.index'))
        ->assertOk()
        ->assertDontSee('Dodaj źródło wyników')
        ->assertSee(route('admin.typer.matches.predictions', $match), false)
        ->assertSee('Podgląd typów');
});

it('allows an admin to set match-specific bonus answers with the result', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $competition = Competition::create(['name' => 'Odpowiedzi Liga', 'slug' => 'odpowiedzi-liga']);
    $match = LechMatch::create([
        'competition_id' => $competition->id,
        'opponent' => 'Rywal Odpowiedzi',
        'scheduled_at' => now()->addDay(),
    ]);
    $yesQuestion = BonusQuestion::create([
        'match_id' => $match->id,
        'type' => 'offensive',
        'question_text' => 'Czy Lech oddał więcej strzałów?',
    ]);
    $noQuestion = BonusQuestion::create([
        'match_id' => $match->id,
        'type' => 'defensive',
        'question_text' => 'Czy Lech zachował czyste konto?',
    ]);
    $fan = User::factory()->create(['name' => 'Fan Z Wynikiem']);
    $prediction = Prediction::create([
        'user_id' => $fan->id,
        'match_id' => $match->id,
        'home_score' => 3,
        'away_score' => 1,
    ]);
    UserAnswer::create(['user_id' => $fan->id, 'bonus_question_id' => $yesQuestion->id, 'answer' => true]);
    UserAnswer::create(['user_id' => $fan->id, 'bonus_question_id' => $noQuestion->id, 'answer' => false]);

    $this->actingAs($admin)
        ->patch(route('admin.typer.matches.result.update', $match), [
            'result_home' => 2,
            'result_away' => 1,
            'status' => 'completed',
            'correct_answers' => [
                $yesQuestion->id => '1',
                $noQuestion->id => '0',
            ],
        ])
        ->assertRedirect();

    expect($match->fresh()->result_home)->toBe(2)
        ->and($match->fresh()->result_away)->toBe(1)
        ->and($yesQuestion->fresh()->correct_answer)->toBeTrue()
        ->and($noQuestion->fresh()->correct_answer)->toBeFalse()
        ->and($prediction->fresh()->points_base)->toBe(1)
        ->and($prediction->fresh()->points_offensive)->toBe(1)
        ->and($prediction->fresh()->total_points)->toBe(2);

    $this->actingAs($admin)
        ->get(route('admin.typer.matches.predictions', $match))
        ->assertOk()
        ->assertSee('Czy Lech oddał więcej strzałów?')
        ->assertSee('Czy Lech zachował czyste konto?')
        ->assertSee('Fan Z Wynikiem');
});
