<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['league_round_id', 'pool_question_id', 'type', 'question_text', 'correct_answer'])]
class LeagueRoundBonusQuestion extends Model
{
    protected function casts(): array
    {
        return ['correct_answer' => 'boolean'];
    }

    public function leagueRound(): BelongsTo
    {
        return $this->belongsTo(LeagueRound::class);
    }

    public function poolQuestion(): BelongsTo
    {
        return $this->belongsTo(BonusQuestionPool::class, 'pool_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(MatchSelectionAnswer::class);
    }
}
