<?php

use App\Models\League;
use App\Models\LeagueRound;
use App\Models\MatchGame;
use App\Models\MatchSelection;
use App\Models\Season;
use App\Models\SeasonLeague;
use App\Models\SeasonRound;
use App\Models\SeasonTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;

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
        ->assertSee(route('admin.leagues.index'))
        ->assertSee('Moderacja wpisów');

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee(route('admin.leagues.index'))
        ->assertSee('Moderacja wpisów');

    $this->actingAs($admin)
        ->get(route('admin.leagues.index'))
        ->assertOk()
        ->assertSee(route('admin.dashboard'))
        ->assertSee(route('admin.users.index'))
        ->assertSee('Moderacja wpisów');
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

it('allows an admin to add a users team to a season league', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['name' => 'Kibic Testowy']);
    $seasonLeague = SeasonLeague::query()->firstOrFail();

    $this->actingAs($admin)->post(route('admin.leagues.teams.store'), [
        'user_id' => $user->id,
        'season_league_id' => $seasonLeague->id,
    ])->assertRedirect();

    $team = Team::where('user_id', $user->id)->firstOrFail();

    expect($team->name)->toBe('Kibic Testowy');
    expect(SeasonTeam::where('season_league_id', $seasonLeague->id)->where('team_id', $team->id)->exists())->toBeTrue();
});

it('derives inactivity from the absence of submitted types and removes a regular assignment', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();
    $seasonLeague = SeasonLeague::query()->whereHas('league', fn ($query) => $query->where('level', '<>', 11))->firstOrFail();
    $team = Team::create(['user_id' => $user->id, 'name' => 'Drużyna Sezonowa']);
    $seasonTeam = SeasonTeam::create(['season_league_id' => $seasonLeague->id, 'team_id' => $team->id]);

    expect($seasonTeam->hasSubmittedTypeInSeason())->toBeFalse();

    $this->actingAs($admin)->delete(route('admin.leagues.teams.destroy', $seasonTeam))->assertRedirect();
    expect(SeasonTeam::find($seasonTeam->id))->toBeNull();
    expect(User::find($user->id))->not->toBeNull();
});

it('creates a new season with only backyard teams that submitted a type', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $activeUser = User::factory()->create(['name' => 'Aktywny Kibic']);
    $inactiveUser = User::factory()->create(['name' => 'Nieaktywny Kibic']);
    $season = Season::query()->where('status', 'active')->firstOrFail();
    $backyardLeague = SeasonLeague::query()->where('season_id', $season->id)->whereHas('league', fn ($query) => $query->where('level', 11))->firstOrFail();
    $activeTeam = Team::create(['user_id' => $activeUser->id, 'name' => 'Aktywna Drużyna']);
    $inactiveTeam = Team::create(['user_id' => $inactiveUser->id, 'name' => 'Nieaktywna Drużyna']);
    SeasonTeam::create(['season_league_id' => $backyardLeague->id, 'team_id' => $activeTeam->id]);
    SeasonTeam::create(['season_league_id' => $backyardLeague->id, 'team_id' => $inactiveTeam->id]);
    $match = MatchGame::create([
        'season_league_id' => $backyardLeague->id,
        'round_number' => 1,
        'home_team_id' => $activeTeam->id,
        'away_team_id' => $inactiveTeam->id,
        'scheduled_at' => now()->addDay(),
    ]);
    MatchSelection::create(['match_id' => $match->id, 'team_id' => $activeTeam->id, 'submitted_at' => now()]);

    $this->actingAs($admin)->post(route('admin.leagues.seasons.create'), [
        'name' => 'Sezon 2027/28',
        'starts_at' => '2027-09-01',
        'ends_at' => '2027-11-30',
        'rounds' => 9,
    ])->assertRedirect();

    $newSeason = Season::query()->where('name', 'Sezon 2027/28')->firstOrFail();
    $newBackyard = $newSeason->seasonLeagues()->where('league_id', $backyardLeague->league_id)->firstOrFail();

    expect(SeasonTeam::where('season_league_id', $newBackyard->id)->where('team_id', $activeTeam->id)->exists())->toBeTrue();
    expect(SeasonTeam::where('season_league_id', $newBackyard->id)->where('team_id', $inactiveTeam->id)->exists())->toBeFalse();
    expect(User::find($inactiveUser->id))->toBeNull();
});

it('does not delete a backyard league team before the season ends', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();
    $seasonLeague = SeasonLeague::query()->whereHas('league', fn ($query) => $query->where('level', 11))->firstOrFail();
    $seasonLeague->season->update(['ends_at' => Carbon::tomorrow()]);
    $team = Team::create(['user_id' => $user->id, 'name' => 'Drużyna Podwórkowa']);
    $seasonTeam = SeasonTeam::create(['season_league_id' => $seasonLeague->id, 'team_id' => $team->id]);

    $this->actingAs($admin)->delete(route('admin.leagues.teams.destroy', $seasonTeam))->assertRedirect();
    expect(SeasonTeam::find($seasonTeam->id))->not->toBeNull();
    expect(User::find($user->id))->not->toBeNull();
});

it('generates one round robin schedule with administrator supplied dates', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $seasonLeague = SeasonLeague::query()->whereHas('league', fn ($query) => $query->where('level', '<>', 11))->firstOrFail();

    foreach (range(1, 4) as $number) {
        $user = User::factory()->create(['name' => "Kibic {$number}"]);
        $team = Team::create(['user_id' => $user->id, 'name' => "Druzyna {$number}"]);
        SeasonTeam::create(['season_league_id' => $seasonLeague->id, 'team_id' => $team->id]);
    }

    $this->actingAs($admin)->post(route('admin.leagues.schedule.generate'), [
        'season_league_id' => $seasonLeague->id,
    ])->assertRedirect();

    expect($seasonLeague->matches()->count())->toBe(18);
    expect($seasonLeague->matches()->distinct('round_number')->count('round_number'))->toBe(9);
});

it('creates nine rounds and every pair once for ten supporter teams', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $seasonLeague = SeasonLeague::query()->whereHas('league', fn ($query) => $query->where('level', '<>', 11))->firstOrFail();

    foreach (range(1, 10) as $number) {
        $user = User::factory()->create(['name' => "Kibic {$number}"]);
        $team = Team::create(['user_id' => $user->id, 'name' => "Druzyna {$number}"]);
        SeasonTeam::create(['season_league_id' => $seasonLeague->id, 'team_id' => $team->id]);
    }

    $this->actingAs($admin)->post(route('admin.leagues.schedule.generate'), [
        'season_league_id' => $seasonLeague->id,
    ])->assertRedirect();

    $matches = $seasonLeague->matches()->get();
    $pairs = $matches->map(fn ($match): string => collect([$match->home_team_id, $match->away_team_id])->sort()->implode('-'));

    expect($matches)->toHaveCount(45);
    expect($matches->groupBy('round_number')->map->count()->values()->all())->toBe([5, 5, 5, 5, 5, 5, 5, 5, 5]);
    expect($pairs->unique())->toHaveCount(45);
});

it('creates empty league rounds and assigns a real Lech match to a round', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $seasonLeague = SeasonLeague::query()->whereHas('league', fn ($query) => $query->where('level', '<>', 11))->firstOrFail();
    $dates = array_map(fn ($day) => "2026-10-{$day} 18:00", range(1, 9));

    $this->actingAs($admin)->post(route('admin.leagues.schedule.generate'), [
        'season_league_id' => $seasonLeague->id,
        'round_dates' => $dates,
    ])->assertRedirect();

    $round = LeagueRound::where('season_league_id', $seasonLeague->id)->firstOrFail();
    $this->actingAs($admin)->put(route('admin.leagues.rounds.update', $round), [
        'scheduled_at' => '2026-10-01 18:00',
        'real_match_at' => '2026-10-01 20:30',
        'real_home_team' => 'Lech Poznan',
        'real_away_team' => 'Widzew Lodz',
        'competition' => 'Ekstraklasa',
    ])->assertRedirect();

    expect($round->fresh()->real_away_team)->toBe('Widzew Lodz');
    expect($seasonLeague->rounds()->count())->toBe(9);
});

it('creates season rounds automatically and saves the real Lech match in the schedule tab', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $season = Season::query()->where('status', 'active')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.schedule.index'))
        ->assertOk()
        ->assertSee('Terminarz');

    expect($season->seasonRounds()->count())->toBe((int) $season->getRawOriginal('rounds'));

    $round = SeasonRound::query()->where('season_id', $season->id)->where('round_number', 1)->firstOrFail();
    $this->actingAs($admin)->put(route('admin.schedule.rounds.update', $round), [
        'real_match_at' => '2026-09-25 20:00',
        'real_home_team' => 'Crystal Palace',
        'real_away_team' => 'Lech Poznań',
        'competition' => 'Liga Europy',
    ])->assertRedirect();

    expect($round->fresh()->real_home_team)->toBe('Crystal Palace');
    expect($round->fresh()->competition)->toBe('Liga Europy');
});

it('accepts only the configured competition types for a season round', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $season = Season::query()->where('status', 'active')->firstOrFail();
    $this->actingAs($admin)->get(route('admin.schedule.index'))->assertOk();
    $round = SeasonRound::query()->where('season_id', $season->id)->firstOrFail();

    $this->actingAs($admin)->put(route('admin.schedule.rounds.update', $round), [
        'real_match_at' => '2026-09-25 20:00',
        'real_home_team' => 'Crystal Palace',
        'real_away_team' => 'Lech Poznań',
        'competition' => 'Nieznane rozgrywki',
    ])->assertSessionHasErrors('competition');
});
