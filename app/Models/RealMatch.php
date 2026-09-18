<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['season_id', 'season_round_id', 'scheduled_at', 'home_team', 'away_team', 'competition'])]
class RealMatch extends Model
{
    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function seasonRound(): BelongsTo
    {
        return $this->belongsTo(SeasonRound::class);
    }
}
