<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Season;
use App\Models\SeasonLeague;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $season = Season::query()->updateOrCreate(
            ['name' => 'Sezon 2026/27'],
            [
                'starts_at' => '2026-09-19',
                'ends_at' => '2026-11-21',
                'status' => 'active',
                'rounds' => 9,
            ],
        );

        foreach (League::query()->orderBy('level')->get() as $league) {
            SeasonLeague::query()->updateOrCreate(
                ['season_id' => $season->id, 'league_id' => $league->id],
                [
                    'promotion_places' => $league->level === 1 ? 0 : 4,
                    'relegation_places' => $league->level === 11 ? 0 : 4,
                ],
            );
        }
    }
}
