<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['match_id', 'team_id', 'submitted_at'])]
class MatchSelection extends Model
{
    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(MatchSelectionPlayer::class);
    }
}
