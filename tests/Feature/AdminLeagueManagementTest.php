<?php

use App\Models\League;
use App\Models\Season;
use App\Models\User;

it('allows an admin to open league management', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.leagues.index'))
        ->assertOk()
        ->assertSee('Zarządzanie ligami');
});

it('exposes the league link from the admin navigation', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.leagues.index'));

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee(route('admin.leagues.index'));
});

it('allows an admin to create and attach a league to a season', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $season = Season::query()->firstOrFail();

    $this->actingAs($admin)->post(route('admin.leagues.store'), [
        'name' => 'Liga Testowa',
        'slug' => 'liga-testowa',
        'level' => 99,
    ])->assertRedirect();

    $league = League::query()->where('slug', 'liga-testowa')->firstOrFail();

    $this->actingAs($admin)->post(route('admin.leagues.seasons.store'), [
        'season_id' => $season->id,
        'league_id' => $league->id,
        'promotion_places' => 2,
        'relegation_places' => 2,
    ])->assertRedirect();

    expect($season->seasonLeagues()->where('league_id', $league->id)->exists())->toBeTrue();
});
