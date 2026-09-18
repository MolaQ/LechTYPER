<?php

use App\Models\Season;
use App\Models\SeasonTeam;
use App\Models\Team;
use App\Models\User;

it('allows guests to browse the public league dashboard', function () {
    $this->get(route('league.index'))
        ->assertOk()
        ->assertSee('Publiczny podgląd')
        ->assertSee('Terminarz');
});

it('keeps the league section on the homepage for a logged-in fan', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    expect(Team::where('user_id', $user->id)->count())->toBe(1);
    expect(Season::where('status', 'active')->count())->toBe(1);
    expect(SeasonTeam::where('team_id', Team::where('user_id', $user->id)->value('id'))->count())->toBe(1);
    $response->assertSee('Liga kiboli');
});

it('exposes the league link to guests on the homepage', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Liga kiboli')
        ->assertSee('Tabela ligowa')
        ->assertSee('Terminarz ligi')
        ->assertSee('Chłopaki z orlika');

    expect($response->viewData('leaguePositions'))->toHaveCount(10);
    expect($response->viewData('leagueMatches'))->toHaveCount(45);
});
