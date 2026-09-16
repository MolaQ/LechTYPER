<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'player_id', 'injury_until'])]
class TeamPlayer extends Model
{
    protected function casts(): array
    {
        return ['injury_until' => 'datetime'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function isInjured(): bool
    {
        return $this->injury_until?->isFuture() ?? false;
    }
}
