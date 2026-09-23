<?php

declare(strict_types=1);

use App\Models\Competition;
use App\Models\LechMatch;
use App\Models\User;

it('shows question selects and the draw button on the match management page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $competition = Competition::create(['name' => 'Widok Liga', 'slug' => 'widok-liga']);
    LechMatch::create(['competition_id' => $competition->id, 'opponent' => 'Widok Rywal', 'scheduled_at' => now()->addDay()]);

    $match = LechMatch::query()->where('opponent', 'Widok Rywal')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.typer.index'))
        ->assertOk()
        ->assertSee('Mecze Lecha');

    $this->actingAs($admin)
        ->get(route('admin.typer.matches.predictions', $match))
        ->assertOk()
        ->assertSee('Losuj puste miejsca')
        ->assertSee('Wybierz pytanie 1');
});

it('allows an admin to create a Lech match before assigning bonus questions', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $competition = Competition::create(['name' => 'Test Puchar', 'slug' => 'test-puchar']);

    $this->actingAs($admin)->post(route('admin.typer.matches.store'), [
        'competition_id' => $competition->id,
        'opponent' => 'Testowy Rywal',
        'lech_home' => 1,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i'),
    ])->assertRedirect();

    $match = LechMatch::query()->where('opponent', 'Testowy Rywal')->firstOrFail();

    expect($match->bonusQuestions)->toHaveCount(0);
});
