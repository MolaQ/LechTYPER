<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['season_league_id', 'position', 'team_id', 'bot_name', 'inherited_points'])]
class LeaguePosition extends Model
{
    protected function casts(): array
    {
        return ['inherited_points' => 'integer'];
    }

    public function seasonLeague(): BelongsTo
    {
        return $this->belongsTo(SeasonLeague::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function displayName(): string
    {
        return $this->team?->name ?? $this->bot_name;
    }
}
