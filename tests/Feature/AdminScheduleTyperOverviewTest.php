<?php

declare(strict_types=1);

use App\Models\Competition;
use App\Models\LechMatch;
use App\Models\Prediction;
use App\Models\User;

it('shows every Lech match with its round assignment and vote count on the schedule page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $voter = User::factory()->create();
    $competition = Competition::create(['name' => 'Terminarz Liga', 'slug' => 'terminarz-liga']);
    $match = LechMatch::create([
        'competition_id' => $competition->id,
        'round_number' => 4,
        'opponent' => 'Rywal Terminarza',
        'scheduled_at' => now()->addDay(),
    ]);
    Prediction::create(['user_id' => $voter->id, 'match_id' => $match->id, 'home_score' => 1, 'away_score' => 0]);

    $this->actingAs($admin)
        ->get(route('admin.schedule.index'))
        ->assertOk()
        ->assertSee('Rywal Terminarza')
        ->assertSee('Kolejka 4')
        ->assertSeeInOrder(['Głosy', '1']);
});
