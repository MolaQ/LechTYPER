<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['match_selection_id', 'player_id', 'points'])]
class MatchSelectionPlayer extends Model
{
    public function selection(): BelongsTo
    {
        return $this->belongsTo(MatchSelection::class, 'match_selection_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
