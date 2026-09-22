<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['question_text', 'is_active'])]
class BonusQuestionPool extends Model
{
    protected $table = 'bonus_question_pool';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BonusQuestion::class, 'pool_question_id');
    }
}
