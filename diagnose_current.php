<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$season = App\Models\Season::where('status', 'active')->first();
echo "SEASON {$season?->id}\n";
foreach (App\Models\LechMatch::with('predictions.user', 'bonusQuestions')->orderByDesc('scheduled_at')->get() as $match) {
    echo "LECH {$match->id}|round={$match->round_number}|{$match->opponent}|status={$match->status}|result={$match->result_home}:{$match->result_away}|predictions={$match->predictions->count()}|questions={$match->bonusQuestions->count()}\n";
    foreach ($match->predictions as $prediction) {
        $team = App\Models\Team::where('user_id', $prediction->user_id)->first();
        echo "  PRED user={$prediction->user_id}|team={$team?->id}|{$prediction->home_score}:{$prediction->away_score}|points={$prediction->points_base}+{$prediction->points_offensive}={$prediction->total_points}\n";
    }
}
if ($season) {
    foreach ($season->seasonLeagues()->with('league')->get() as $seasonLeague) {
        $round = $seasonLeague->rounds()->where('round_number', 1)->first();
        echo "LEAGUE {$seasonLeague->id}|{$seasonLeague->league->name}|round1={$round?->id}|real={$round?->real_score_home}:{$round?->real_score_away}\n";
        foreach ($seasonLeague->matches()->where('round_number', 1)->with(['homeTeam','awayTeam','selections'])->get() as $match) {
            echo "  GAME {$match->id}|{$match->homeTeam->name}-{$match->awayTeam->name}|status={$match->status}|score={$match->home_score}:{$match->away_score}\n";
            foreach ($match->selections as $selection) echo "    SEL team={$selection->team_id}|{$selection->home_score}:{$selection->away_score}|points={$selection->points_base}+{$selection->points_offensive}={$selection->total_points}\n";
        }
    }
}
