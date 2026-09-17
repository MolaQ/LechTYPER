<?php

use App\Models\Team;
use App\Models\User;

it('allows a user to change the team name independently from the X account name', function () {
    $user = User::factory()->create([
        'name' => 'Jan Kibic',
        'x_username' => '@jan_kibic',
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Jan Kibic')
        ->assertSee('@jan_kibic');

    $this->actingAs($user)->put(route('profile.team.update'), [
        'team_name' => 'Niebieska Brygada',
    ])->assertRedirect();

    expect(Team::where('user_id', $user->id)->value('name'))->toBe('Niebieska Brygada');
    expect(User::find($user->id)->x_username)->toBe('@jan_kibic');

    $this->actingAs($user)->put(route('profile.team.update'), [
        'team_name' => 'Kolejorz 1922',
    ])->assertRedirect();

    expect(Team::where('user_id', $user->id)->value('name'))->toBe('Kolejorz 1922');
});
