<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BonusQuestionPool;
use App\Models\LechMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminBonusController extends Controller
{
    private const TYPES = ['offensive', 'defensive'];

    public function index(): View
    {
        return view('admin.bonuses.index', [
            'matches' => LechMatch::query()->with(['competition', 'bonusQuestions.poolQuestion'])->orderByDesc('scheduled_at')->get(),
            'questions' => BonusQuestionPool::query()->where('is_active', true)->with('assignments.answers')->orderBy('question_text')->get(),
        ]);
    }

    public function storeQuestion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'question_text' => ['required', 'string', 'max:500'],
        ]);

        BonusQuestionPool::query()->firstOrCreate(['question_text' => $data['question_text']], ['is_active' => true]);

        return back()->with('status', 'Pytanie zostało dodane do globalnej puli.');
    }

    public function assign(Request $request, LechMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'offensive' => ['array', 'size:5'],
            'offensive.*' => ['nullable', 'integer', 'distinct', 'exists:bonus_question_pool,id'],
            'defensive' => ['array', 'size:5'],
            'defensive.*' => ['nullable', 'integer', 'distinct', 'exists:bonus_question_pool,id'],
        ]);

        $selectedIds = collect($data['offensive'] ?? [])->merge($data['defensive'] ?? [])->filter()->values();
        if ($selectedIds->count() !== $selectedIds->unique()->count()) {
            return back()->withErrors(['questions' => 'To samo pytanie nie może być użyte dwa razy w meczu.']);
        }

        DB::transaction(function () use ($match, $data): void {
            $assignedIds = $match->bonusQuestions()->pluck('pool_question_id');

            foreach (['offensive', 'defensive'] as $type) {
                foreach ($data[$type] ?? [] as $poolQuestionId) {
                    if ($poolQuestionId === null || $assignedIds->contains($poolQuestionId)) {
                        continue;
                    }

                    $poolQuestion = BonusQuestionPool::query()->findOrFail($poolQuestionId);
                    $match->bonusQuestions()->create([
                        'pool_question_id' => $poolQuestion->id,
                        'type' => $type,
                        'question_text' => $poolQuestion->question_text,
                    ]);
                }
            }
        });

        return back()->with('status', 'Pytania zostały przypisane do meczu i zablokowane.');
    }

    public function drawMissing(Request $request, LechMatch $match): RedirectResponse
    {
        $assigned = $match->bonusQuestions()->pluck('pool_question_id')->filter()->values();
        DB::transaction(function () use ($match, &$assigned): void {
            foreach (self::TYPES as $type) {
                $alreadyAssigned = $match->bonusQuestions()->where('type', $type)->pluck('pool_question_id');
                $missing = max(0, 5 - $alreadyAssigned->count());
                if ($missing === 0) {
                    $assigned = $assigned->merge($alreadyAssigned);

                    continue;
                }

                $questions = BonusQuestionPool::query()
                    ->where('is_active', true)
                    ->whereNotIn('id', $assigned->values()->all())
                    ->inRandomOrder()
                    ->limit($missing)
                    ->get();

                if ($questions->count() < $missing) {
                    abort(422, "Brak pytań typu {$type}: znaleziono {$questions->count()}, wymagane {$missing}.");
                }

                foreach ($questions as $question) {
                    $match->bonusQuestions()->create([
                        'pool_question_id' => $question->id,
                        'type' => $type,
                        'question_text' => $question->question_text,
                    ]);
                    $assigned->push($question->id);
                }
            }
        });

        return back()->with('status', 'Wolne pozycje bonusowe zostały losowo uzupełnione.');
    }
}
