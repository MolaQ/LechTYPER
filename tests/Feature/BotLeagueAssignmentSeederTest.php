<?php

use App\Models\Season;
use App\Models\SeasonLeague;
use App\Models\SeasonTeam;
use App\Models\Team;
use Database\Seeders\BotLeagueAssignmentSeeder;
use Database\Seeders\BotTeamSeeder;

it('fills regular leagues and sends remaining bots to the backyard league', function () {
    $this->seed([BotTeamSeeder::class, BotLeagueAssignmentSeeder::class]);
    $this->seed(BotLeagueAssignmentSeeder::class);

    $season = Season::query()->where('status', 'active')->firstOrFail();
    $regularLeagues = SeasonLeague::query()
        ->where('season_id', $season->id)
        ->whereHas('league', fn ($query) => $query->whereBetween('level', [1, 10]))
        ->withCount('teams')
        ->get();
    $backyardLeague = SeasonLeague::query()
        ->where('season_id', $season->id)
        ->whereHas('league', fn ($query) => $query->where('level', 11))
        ->firstOrFail();
    $regularPositionNames = SeasonLeague::query()
        ->where('season_id', $season->id)
        ->whereHas('league', fn ($query) => $query->whereBetween('level', [1, 10]))
        ->with('positions.team')
        ->get()
        ->flatMap(fn (SeasonLeague $league) => $league->positions->map(fn ($position) => $position->displayName()));
    $botTeamIds = Team::query()
        ->whereHas('user', fn ($query) => $query->where('email', 'like', 'bot.%@lechtyper.local'))
        ->pluck('id');
    $regularBotAssignments = SeasonTeam::query()
        ->whereIn('team_id', $botTeamIds)
        ->whereHas('seasonLeague', function ($query) use ($season): void {
            $query->where('season_id', $season->id)->whereHas('league', fn ($query) => $query->whereBetween('level', [1, 10]));
        })
        ->count();

    expect($regularLeagues)->toHaveCount(10)
        ->and($regularLeagues->every(fn (SeasonLeague $league): bool => $league->teams_count === 10))->toBeTrue()
        ->and($regularPositionNames)->not->toContain('Chłopaki z orlika')
        ->and(SeasonTeam::query()->where('season_league_id', $backyardLeague->id)->whereIn('team_id', $botTeamIds)->count())->toBe($botTeamIds->count() - $regularBotAssignments);
});
