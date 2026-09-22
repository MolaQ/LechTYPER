<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BonusQuestionPool;
use App\Models\Competition;
use App\Models\LechMatch;
use App\Models\Prediction;
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

        return back()->with('status', 'Wynik i poprawne odpowiedzi zostały zapisane.');
    }

    public function predictions(LechMatch $match): View
    {
        return view('admin.typer.predictions', [
            'match' => $match->load('competition'),
            'predictions' => Prediction::query()
                ->where('match_id', $match->id)
                ->with(['user', 'match.bonusQuestions'])
                ->orderByDesc('total_points')
                ->get(),
        ]);
    }
}
