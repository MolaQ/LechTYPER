<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['season_league_id', 'team_id', 'position', 'played', 'wins', 'draws', 'losses', 'points', 'bonus_points', 'score_for', 'score_against'])]
class SeasonTeam extends Model
{
    protected static function booted(): void
    {
        static::creating(function (SeasonTeam $seasonTeam): void {
            $duplicateExists = static::query()
                ->where('team_id', $seasonTeam->team_id)
                ->whereHas('seasonLeague', fn ($query) => $query->where('season_id', $seasonTeam->seasonLeague?->season_id ?? $seasonTeam->seasonLeague()->value('season_id')))
                ->exists();

            if ($duplicateExists) {
                throw new \RuntimeException('Ta drużyna jest już przypisana do innej ligi w tym sezonie.');
            }
        });
    }

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
