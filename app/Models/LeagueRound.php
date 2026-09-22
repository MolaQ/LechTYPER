<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['season_league_id', 'round_number', 'scheduled_at', 'real_match_at', 'real_home_team', 'real_away_team', 'competition', 'real_score_home', 'real_score_away'])]
class LeagueRound extends Model
{
    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'real_match_at' => 'datetime'];
    }

    public function seasonLeague(): BelongsTo
    {
        return $this->belongsTo(SeasonLeague::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchGame::class);
    }

    public function bonusQuestions(): HasMany
    {
        return $this->hasMany(LeagueRoundBonusQuestion::class);
    }
}
