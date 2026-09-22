<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CalculateMatchPointsAction;
use App\Models\BonusQuestionPool;
use App\Models\Competition;
use App\Models\LeagueRound;
use App\Models\LechMatch;
use App\Models\MatchSelection;
use App\Models\MatchSelectionAnswer;
use App\Models\Prediction;
use App\Models\Season;
use App\Models\Team;
use App\Services\LeagueMatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTyperController extends Controller
{
    public function index(): View
    {
        return view('admin.typer.index', [
            'matches' => LechMatch::query()->with(['competition', 'bonusQuestions.poolQuestion'])->orderByDesc('scheduled_at')->get(),
            'competitions' => Competition::query()->orderBy('name')->get(),
            'questions' => BonusQuestionPool::query()->where('is_active', true)->orderBy('question_text')->get(),
        ]);
    }

    public function storeMatch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'competition_id' => ['required', 'integer', 'exists:competitions,id'],
            'round_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'opponent' => ['required', 'string', 'max:120'],
            'lech_home' => ['required', 'boolean'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $match = LechMatch::query()->create([
            'competition_id' => $data['competition_id'],
            'round_number' => $data['round_number'] ?? null,
            'opponent' => $data['opponent'],
            'lech_home' => (bool) $data['lech_home'],
            'scheduled_at' => $data['scheduled_at'],
            'status' => 'scheduled',
        ]);

        return back()->with('status', 'Mecz został dodany. Pytania wybierzesz w formularzu meczu.');
    }

    public function updateMatch(Request $request, LechMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'competition_id' => ['required', 'integer', 'exists:competitions,id'],
            'round_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'opponent' => ['required', 'string', 'max:120'],
            'lech_home' => ['required', 'boolean'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $match->update([
            'competition_id' => $data['competition_id'],
            'round_number' => $data['round_number'] ?? null,
            'opponent' => $data['opponent'],
            'lech_home' => (bool) $data['lech_home'],
            'scheduled_at' => $data['scheduled_at'],
        ]);

        return back()->with('status', 'Mecz został zaktualizowany.');
    }

    public function updateResult(Request $request, LechMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'result_home' => ['required', 'integer', 'min:0', 'max:99'],
            'result_away' => ['required', 'integer', 'min:0', 'max:99'],
            'status' => ['required', 'in:scheduled,completed,cancelled'],
            'correct_answers' => ['array'],
            'correct_answers.*' => ['nullable', 'boolean'],
        ]);

        $match->update([
            'result_home' => $data['result_home'],
            'result_away' => $data['result_away'],
            'status' => $data['status'],
        ]);

        foreach ($match->bonusQuestions as $question) {
            $question->update(['correct_answer' => $data['correct_answers'][$question->id] ?? null]);
        }

        app(CalculateMatchPointsAction::class)->settleMatch($match->fresh(['bonusQuestions', 'h2hFixtures']));
        $this->settleMatchingLeagueRounds($match->fresh('bonusQuestions'));

        return back()->with('status', 'Wynik i poprawne odpowiedzi zostały zapisane.');
    }

    private function settleMatchingLeagueRounds(LechMatch $match): void
    {
        if ($match->round_number === null || $match->result_home === null || $match->result_away === null) {
            return;
        }

        $season = Season::query()->where('status', 'active')->first();
        if ($season === null) {
            return;
        }

        $homeScore = $match->lech_home ? $match->result_home : $match->result_away;
        $awayScore = $match->lech_home ? $match->result_away : $match->result_home;

        LeagueRound::query()
            ->where('round_number', $match->round_number)
            ->whereHas('seasonLeague', fn ($query) => $query
                ->where('season_id', $season->id)
                ->whereHas('league', fn ($leagueQuery) => $leagueQuery->where('level', '<>', 11)))
            ->with('bonusQuestions')
            ->get()
            ->each(function (LeagueRound $round) use ($match, $homeScore, $awayScore): void {
                $correctAnswers = [];
                foreach ($round->bonusQuestions as $question) {
                    $sourceQuestion = $match->bonusQuestions->firstWhere('question_text', $question->question_text);
                    $correctAnswers[$question->id] = $sourceQuestion?->correct_answer;
                }

                $this->syncPredictionsToLeagueRound($match, $round);
                app(LeagueMatchService::class)->completeRound($round, (int) $homeScore, (int) $awayScore, $correctAnswers);
            });
    }

    private function syncPredictionsToLeagueRound(LechMatch $sourceMatch, LeagueRound $round): void
    {
        $round->loadMissing('matches');

        foreach ($round->matches as $leagueMatch) {
            $teamIds = [$leagueMatch->home_team_id, $leagueMatch->away_team_id];
            $predictions = $sourceMatch->predictions()->with('user')->get();

            foreach ($predictions as $prediction) {
                $teamId = Team::query()->where('user_id', $prediction->user_id)->value('id');
                if ($teamId === null || ! in_array($teamId, $teamIds, true)) {
                    continue;
                }

                $selection = MatchSelection::updateOrCreate(
                    ['match_id' => $leagueMatch->id, 'team_id' => $teamId],
                    [
                        'home_score' => $prediction->home_score,
                        'away_score' => $prediction->away_score,
                        'submitted_at' => $prediction->updated_at ?? now(),
                    ],
                );

                foreach ($round->bonusQuestions as $question) {
                    $sourceQuestion = $sourceMatch->bonusQuestions->firstWhere('question_text', $question->question_text);
                    $answer = $sourceQuestion === null
                        ? null
                        : $sourceQuestion->answers()->where('user_id', $prediction->user_id)->value('answer');
                    MatchSelectionAnswer::updateOrCreate(
                        ['match_selection_id' => $selection->id, 'league_round_bonus_question_id' => $question->id],
                        ['answer' => $answer],
                    );
                }
            }
        }
    }

    public function predictions(LechMatch $match): View
    {
        $match->load(['competition', 'bonusQuestions']);
        $predictions = Prediction::query()
            ->where('match_id', $match->id)
            ->with(['user', 'answers.question'])
            ->orderByDesc('total_points')
            ->get();

        return view('admin.typer.predictions', [
            'match' => $match,
            'predictions' => $predictions,
            'offensiveQuestions' => $match->bonusQuestions->where('type', 'offensive')->values(),
            'defensiveQuestions' => $match->bonusQuestions->where('type', 'defensive')->values(),
        ]);
    }
}
