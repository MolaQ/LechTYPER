<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['season_league_id', 'team_id', 'position', 'played', 'wins', 'draws', 'losses', 'points', 'score_for', 'score_against'])]
class SeasonTeam extends Model
{
    public function seasonLeague(): BelongsTo
    {
        return $this->belongsTo(SeasonLeague::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
