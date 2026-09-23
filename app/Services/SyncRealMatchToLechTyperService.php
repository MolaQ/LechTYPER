<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Competition;
use App\Models\LechMatch;
use App\Models\RealMatch;
use Illuminate\Support\Str;

class SyncRealMatchToLechTyperService
{
    public function sync(RealMatch $realMatch): LechMatch
    {
        $realMatch->loadMissing('seasonRound');
        $lechIsHome = Str::lower($realMatch->home_team) === Str::lower('Lech Poznań');
        $opponent = $lechIsHome ? $realMatch->away_team : $realMatch->home_team;
        $competition = Competition::query()->firstOrCreate(
            ['slug' => Str::slug($realMatch->competition)],
            ['name' => $realMatch->competition],
        );

        $match = LechMatch::query()->firstOrNew([
            'scheduled_at' => $realMatch->scheduled_at,
            'opponent' => $opponent,
            'lech_home' => $lechIsHome,
        ]);
        $match->fill([
            'competition_id' => $competition->id,
            'season_id' => $realMatch->season_id,
            'round_number' => $realMatch->seasonRound?->round_number,
            'scheduled_at' => $realMatch->scheduled_at,
            'opponent' => $opponent,
            'lech_home' => $lechIsHome,
            'status' => $match->status ?? 'scheduled',
        ]);
        $match->save();

        return $match;
    }
}
