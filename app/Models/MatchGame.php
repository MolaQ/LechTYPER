<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['season_league_id', 'round_number', 'home_team_id', 'away_team_id', 'scheduled_at', 'status', 'home_score', 'away_score'])]
class MatchGame extends Model
{
    protected $table = 'matches';

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function seasonLeague(): BelongsTo
    {
        return $this->belongsTo(SeasonLeague::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function selections(): HasMany
    {
        return $this->hasMany(MatchSelection::class);
    }
}
