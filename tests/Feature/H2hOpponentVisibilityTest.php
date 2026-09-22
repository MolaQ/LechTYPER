<?php

declare(strict_types=1);

use App\Livewire\LechTyper\MatchPrediction;
use App\Models\Competition;
use App\Models\H2hFixture;
use App\Models\LechMatch;
use App\Models\Prediction;
use App\Models\User;
use Livewire\Livewire;

it('hides the H2H opponent prediction until the match kicks off', function () {
    $user = User::factory()->create();
    $opponent = User::factory()->create(['name' => 'Rywal H2H']);
    $competition = Competition::create(['name' => 'H2H Liga', 'slug' => 'h2h-liga']);
    $match = LechMatch::create([
        'competition_id' => $competition->id,
        'opponent' => 'Widzew',
        'scheduled_at' => now()->addHour(),
    ]);
    H2hFixture::create(['user_id' => $user->id, 'opponent_id' => $opponent->id, 'match_id' => $match->id]);
    Prediction::create(['user_id' => $opponent->id, 'match_id' => $match->id, 'home_score' => 2, 'away_score' => 1]);

    $component = Livewire::actingAs($user)->test(MatchPrediction::class);
    $component->assertSee('Rywal H2H')
        ->assertSee('Typ rywala odsłoni się po rozpoczęciu meczu.')
        ->assertDontSee('2:1');

    $match->update(['scheduled_at' => now()->subMinute()]);

    $component->call('toggleAnswer', 999, null)
        ->assertDontSee('odsłoni się po rozpoczęciu')
        ->assertSee('2:1');
});
