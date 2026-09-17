<?php

use App\Models\User;

test('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk();
});

test('news and league routes work for authenticated users', function () {
    $user = User::factory()->create([
        'role' => 'kibol',
    ]);

    $this->actingAs($user)
        ->get('/aktualnosci')
        ->assertOk();

    $this->actingAs($user)
        ->get('/liga')
        ->assertOk();
});
