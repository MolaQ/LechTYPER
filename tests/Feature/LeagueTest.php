<?php

use App\Models\BonusQuestion;
use App\Models\Competition;
use App\Models\League;
use App\Models\LechMatch;
use App\Models\Season;
use App\Models\SeasonLeague;
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

it('shows the assigned typer match in the league round schedule', function () {
    $competition = Competition::create(['name' => 'Liga Testowa', 'slug' => 'liga-testowa']);
    LechMatch::create([
        'competition_id' => $competition->id,
        'round_number' => 1,
        'opponent' => 'Testowy Rywal',
        'lech_home' => true,
        'scheduled_at' => now()->addDay(),
    ]);

    $this->get(route('league.index'))
        ->assertOk()
        ->assertSee('Lech Poznań - Testowy Rywal');
});

it('uses Swiss league rounds and exposes completed Lech match details', function () {
    $competition = Competition::create(['name' => 'Szwajcarska Liga', 'slug' => 'szwajcarska-liga']);
    $match = LechMatch::create([
        'competition_id' => $competition->id,
        'round_number' => 1,
        'opponent' => 'Jagiellonia Białystok',
        'lech_home' => true,
        'scheduled_at' => now()->subHour(),
        'result_home' => 2,
        'result_away' => 1,
        'status' => 'completed',
    ]);
    BonusQuestion::create([
        'match_id' => $match->id,
        'type' => 'offensive',
        'question_text' => 'Czy Lech strzeli gola?',
        'correct_answer' => true,
    ]);

    $league = League::query()->where('level', 11)->firstOrFail();
    $this->get(route('league.show', $league->slug))
        ->assertOk()
        ->assertSee('Kolejka 1')
        ->assertSee('Jagiellonia Białystok');

    $this->get(route('league.real-match', [$league->slug, $match]))
        ->assertOk()
        ->assertSee('2:1')
        ->assertSee('Czy Lech strzeli gola?')
        ->assertSee('TAK');
});

it('does not re-add a fan team to the backyard league once it is promoted elsewhere', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $fan = User::factory()->create();

    // Auto-assigns the fan to the backyard league on their very first visit.
    $this->actingAs($fan)->get(route('home'))->assertOk();
    $team = Team::where('user_id', $fan->id)->firstOrFail();
    $season = Season::query()->where('status', 'active')->firstOrFail();
    $regularLeague = SeasonLeague::query()
        ->where('season_id', $season->id)
        ->whereHas('league', fn ($query) => $query->where('level', '<>', 11))
        ->firstOrFail();

    $this->actingAs($admin)->post(route('admin.leagues.teams.store'), [
        'user_id' => $fan->id,
        'season_league_id' => $regularLeague->id,
        'position' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->actingAs($fan)->get(route('home'))->assertOk();

    expect(SeasonTeam::where('team_id', $team->id)->count())->toBe(1);
    expect(SeasonTeam::where('team_id', $team->id)->where('season_league_id', $regularLeague->id)->exists())->toBeTrue();
});
