<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['match_selection_id', 'league_round_bonus_question_id', 'answer'])]
class MatchSelectionAnswer extends Model
{
    protected function casts(): array
    {
        return ['answer' => 'boolean'];
    }

    public function selection(): BelongsTo
    {
        return $this->belongsTo(MatchSelection::class, 'match_selection_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(LeagueRoundBonusQuestion::class, 'league_round_bonus_question_id');
    }
}
