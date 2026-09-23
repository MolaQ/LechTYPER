<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$league = App\Models\League::where('level', 11)->firstOrFail();
$season = App\Models\Season::where('status', 'active')->firstOrFail();
$seasonLeague = $season->seasonLeagues()->where('league_id', $league->id)->firstOrFail();
app(App\Services\SeasonTeamCleanupService::class)->reconcile($seasonLeague);
app(App\Services\SwissLeagueService::class)->simulateDueRounds($seasonLeague);
app(App\Services\SwissLeagueService::class)->generateNextRound($seasonLeague);
foreach ($seasonLeague->teams()->with('team')->orderByDesc('points')->get() as $t) {
    echo $t->team->name.'|pts='.$t->points.'|played='.$t->played.'|for='.$t->score_for.':'.$t->score_against.PHP_EOL;
}
