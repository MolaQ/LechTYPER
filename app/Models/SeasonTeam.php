<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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

    public function hasSubmittedTypeInSeason(): bool
    {
        return DB::table('match_selections')
            ->join('matches', 'matches.id', '=', 'match_selections.match_id')
            ->join('season_leagues', 'season_leagues.id', '=', 'matches.season_league_id')
            ->where('season_leagues.season_id', $this->seasonLeague->season_id)
            ->where('match_selections.team_id', $this->team_id)
            ->exists();
    }
}
