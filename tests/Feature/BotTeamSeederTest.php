<?php

use App\Models\Team;
use App\Models\User;
use Database\Seeders\BotTeamSeeder;

it('seeds 256 unique bot teams and is repeatable', function () {
    $this->seed(BotTeamSeeder::class);
    $this->seed(BotTeamSeeder::class);

    $botUsers = User::query()->where('email', 'like', 'bot.___%@lechtyper.local')->get();

    expect($botUsers)->toHaveCount(256)
        ->and(Team::query()->whereIn('user_id', $botUsers->pluck('id'))->count())->toBe(256)
        ->and($botUsers->pluck('name')->unique())->toHaveCount(256);
});
