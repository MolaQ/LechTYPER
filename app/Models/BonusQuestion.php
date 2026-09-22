<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['match_id', 'pool_question_id', 'type', 'question_text', 'correct_answer'])]
class BonusQuestion extends Model
{
    protected function casts(): array
    {
        return ['correct_answer' => 'boolean'];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(LechMatch::class, 'match_id');
    }

    public function poolQuestion(): BelongsTo
    {
        return $this->belongsTo(BonusQuestionPool::class, 'pool_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(UserAnswer::class, 'bonus_question_id');
    }
}
