<?php

declare(strict_types=1);

use App\Models\BonusQuestionPool;
use App\Models\LeagueRound;
use App\Models\Season;
use App\Models\SeasonLeague;
use App\Models\SeasonTeam;
use App\Models\Team;
use App\Models\User;

it('self-heals an orphaned bot season-team row and backfills missing bonus questions', function () {
    foreach (range(1, 10) as $number) {
        BonusQuestionPool::create(['question_text' => "Reconcile pytanie {$number}"]);
    }

    $season = Season::query()->where('status', 'active')->firstOrFail();
    $seasonLeague = SeasonLeague::query()
        ->where('season_id', $season->id)
        ->whereHas('league', fn ($query) => $query->where('level', '<>', 11))
        ->firstOrFail();

    $orphanUser = User::factory()->create(['name' => 'Orphan Bot']);
    $orphanTeam = Team::create(['user_id' => $orphanUser->id, 'name' => 'Orphan Bot']);
    SeasonTeam::create(['season_league_id' => $seasonLeague->id, 'team_id' => $orphanTeam->id]);

    $round = LeagueRound::create([
        'season_league_id' => $seasonLeague->id,
        'round_number' => 1,
        'scheduled_at' => now()->addDay(),
    ]);
    expect($round->bonusQuestions()->count())->toBe(0);

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get(route('admin.leagues.index', ['league' => $seasonLeague->league->slug]))->assertOk();

    expect(SeasonTeam::query()->where('season_league_id', $seasonLeague->id)->where('team_id', $orphanTeam->id)->exists())->toBeFalse();
    expect($round->fresh()->bonusQuestions()->count())->toBe(10);
});
