<?php

declare(strict_types=1);

use App\Models\Competition;
use App\Models\LechMatch;
use App\Models\Prediction;
use App\Models\User;

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
