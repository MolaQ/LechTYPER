<?php

declare(strict_types=1);

namespace App\Livewire\LechTyper;

use App\Models\LechMatch;
use App\Models\Prediction;
use App\Models\H2hFixture;
use App\Models\UserAnswer;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MatchPrediction extends Component
{
    public ?int $matchId = null;

    public int $homeScore = 0;

    public int $awayScore = 0;

    /** @var array<int, bool|null> */
    public array $answers = [];

    public function mount(): void
    {
        $match = LechMatch::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->with('bonusQuestions')
            ->orderBy('scheduled_at')
            ->first();

        if ($match === null) {
            return;
        }

        $this->matchId = $match->id;
        $prediction = Prediction::query()
            ->where('user_id', auth()->id())
            ->where('match_id', $match->id)
            ->first();

        $this->homeScore = $prediction?->home_score ?? 0;
        $this->awayScore = $prediction?->away_score ?? 0;
        $this->answers = UserAnswer::query()
            ->where('user_id', auth()->id())
            ->whereIn('bonus_question_id', $match->bonusQuestions->pluck('id'))
            ->pluck('answer', 'bonus_question_id')
            ->map(fn ($answer): ?bool => $answer === null ? null : (bool) $answer)
            ->all();
    }

    public function increment(string $side): void
    {
        if ($side === 'homeScore') {
            $this->homeScore = min(20, $this->homeScore + 1);
        }

        if ($side === 'awayScore') {
            $this->awayScore = min(20, $this->awayScore + 1);
        }
    }

    public function decrement(string $side): void
    {
        if ($side === 'homeScore') {
            $this->homeScore = max(0, $this->homeScore - 1);
        }

        if ($side === 'awayScore') {
            $this->awayScore = max(0, $this->awayScore - 1);
        }
    }

    public function toggleAnswer(int $questionId, ?bool $value): void
    {
        $this->answers[$questionId] = $value;
    }

    public function save(): void
    {
        abort_unless($this->matchId !== null, 404);

        $match = LechMatch::query()->whereKey($this->matchId)->with('bonusQuestions')->firstOrFail();
        abort_unless($match->status === 'scheduled' && $match->scheduled_at->isFuture(), 422, 'Typowanie zostało zamknięte.');

        DB::transaction(function () use ($match): void {
            Prediction::query()->updateOrCreate(
                ['user_id' => auth()->id(), 'match_id' => $match->id],
                [
                    'home_score' => $this->homeScore,
                    'away_score' => $this->awayScore,
                ],
            );

            foreach ($match->bonusQuestions as $question) {
                UserAnswer::query()->updateOrCreate(
                    ['user_id' => auth()->id(), 'bonus_question_id' => $question->id],
                    ['answer' => $this->answers[$question->id] ?? null],
                );
            }
        });

        session()->flash('status', 'Typ został zapisany.');
    }

    public function render()
    {
        $match = $this->matchId === null
            ? null
            : LechMatch::query()->with(['competition', 'bonusQuestions'])->find($this->matchId);

        return view('livewire.lech-typer.match-prediction', [
            'match' => $match,
            'opponentPrediction' => $match === null ? null : $this->opponentPrediction($match),
        ]);
    }

    private function opponentPrediction(LechMatch $match): ?array
    {
        $fixture = H2hFixture::query()
            ->where('match_id', $match->id)
            ->where('user_id', auth()->id())
            ->with('opponent')
            ->first();

        if ($fixture === null) {
            return null;
        }

        $opponentPrediction = Prediction::query()
            ->where('match_id', $match->id)
            ->where('user_id', $fixture->opponent_id)
            ->first();

        return [
            'name' => $fixture->opponent->name,
            'locked' => ! $match->isTypingClosed(),
            'prediction' => $opponentPrediction,
        ];
    }

    public function offensiveAnsweredCount(): int
    {
        return $this->questionsOfType('offensive')->filter(fn ($question): bool => array_key_exists($question->id, $this->answers) && $this->answers[$question->id] !== null)->count();
    }

    public function defensiveAnsweredCount(): int
    {
        return $this->questionsOfType('defensive')->filter(fn ($question): bool => array_key_exists($question->id, $this->answers) && $this->answers[$question->id] !== null)->count();
    }

    private function questionsOfType(string $type)
    {
        if ($this->matchId === null) {
            return collect();
        }

        return LechMatch::query()->find($this->matchId)?->bonusQuestions()->where('type', $type)->get() ?? collect();
    }
}
