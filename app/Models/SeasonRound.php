<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['season_id', 'round_number', 'scheduled_at', 'real_match_at', 'real_home_team', 'real_away_team', 'competition'])]
class SeasonRound extends Model
{
    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'real_match_at' => 'datetime'];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
