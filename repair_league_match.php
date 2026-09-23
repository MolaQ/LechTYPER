<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$game = App\Models\MatchGame::with(['leagueRound.bonusQuestions','selections'])->findOrFail(1);
$round = $game->leagueRound;
$source = App\Models\LechMatch::with(['bonusQuestions.answers'])->where('round_number', $round->round_number)->whereNotNull('result_home')->whereNotNull('result_away')->orderByDesc('scheduled_at')->firstOrFail();
$sourceByType = $source->bonusQuestions->groupBy('type')->map(fn ($questions) => $questions->values());
$indexes = ['offensive' => 0, 'defensive' => 0];
foreach ($round->bonusQuestions as $question) { $index = $indexes[$question->type]++; $sourceQuestion = $sourceByType->get($question->type, collect())->get($index); if ($sourceQuestion !== null) $question->update(['correct_answer' => $sourceQuestion->correct_answer]); }
$prediction = $source->predictions()->with('user')->first();
$selection = $game->selections->firstWhere('team_id', App\Models\Team::where('user_id', $prediction?->user_id)->value('id'));
if ($prediction && $selection) { $indexes = ['offensive' => 0, 'defensive' => 0]; foreach ($round->bonusQuestions as $question) { $index = $indexes[$question->type]++; $sourceQuestion = $sourceByType->get($question->type, collect())->get($index); $answer = $sourceQuestion?->answers->firstWhere('user_id', $prediction->user_id)?->answer; App\Models\MatchSelectionAnswer::updateOrCreate(['match_selection_id' => $selection->id, 'league_round_bonus_question_id' => $question->id], ['answer' => $answer]); } }
$round = $round->fresh(['bonusQuestions']);
app(App\Services\LeagueMatchService::class)->completeRound($round, (int) $source->result_home, (int) $source->result_away, $round->bonusQuestions->pluck('correct_answer', 'id')->all());
echo 'repaired'.PHP_EOL;
