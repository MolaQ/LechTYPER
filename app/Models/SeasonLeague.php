<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['season_id', 'league_id', 'promotion_places', 'relegation_places'])]
class SeasonLeague extends Model
{
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(SeasonTeam::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchGame::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(LeagueRound::class)->orderBy('round_number');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(LeaguePosition::class)->orderBy('position');
    }
}
