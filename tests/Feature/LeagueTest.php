<?php

use App\Models\Season;
use App\Models\SeasonTeam;
use App\Models\Team;
use App\Models\User;

it('requires authentication for the league dashboard', function () {
    $this->get(route('league.index'))->assertRedirect(route('login'));
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

it('does not expose the league section to guests', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('Liga kiboli');
});
