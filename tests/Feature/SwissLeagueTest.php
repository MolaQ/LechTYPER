<?php

use App\Models\MatchGame;
use App\Models\SeasonLeague;
use App\Models\SeasonTeam;
use App\Models\Team;
use App\Models\User;
use App\Services\SwissLeagueService;

it('generates dynamic Swiss rounds without repeating a pairing', function () {
    $seasonLeague = SeasonLeague::query()
        ->whereHas('league', fn ($query) => $query->where('level', 11))
        ->firstOrFail();
    $teams = collect(range(1, 5))->map(function (int $number) use ($seasonLeague): SeasonTeam {
        $user = User::factory()->create(['name' => "Swiss Kibic {$number}"]);
        $team = Team::create(['user_id' => $user->id, 'name' => "Swiss Team {$number}"]);

        return SeasonTeam::create([
            'season_league_id' => $seasonLeague->id,
            'team_id' => $team->id,
            'points' => 5 - $number,
        ]);
    });

    $service = app(SwissLeagueService::class);
    $firstRound = $service->generateNextRound($seasonLeague);

    expect($firstRound)->not->toBeNull();
    expect($firstRound->matches()->count())->toBe(2);

    MatchGame::query()
        ->where('season_league_id', $seasonLeague->id)
        ->update(['status' => 'completed']);

    $secondRound = $service->generateNextRound($seasonLeague);

    expect($secondRound)->not->toBeNull();
    expect($seasonLeague->rounds()->count())->toBe(2);
    expect($seasonLeague->matches()->count())->toBe(4);

    $pairs = $seasonLeague->matches()->get()->map(
        fn (MatchGame $match): string => collect([$match->home_team_id, $match->away_team_id])->sort()->implode('-'),
    );

    expect($pairs->unique())->toHaveCount(4);
});
