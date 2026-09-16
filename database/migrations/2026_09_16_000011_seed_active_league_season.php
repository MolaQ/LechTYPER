<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seasons') || DB::table('seasons')->where('status', 'active')->exists()) {
            return;
        }

        $seasonId = DB::table('seasons')->insertGetId([
            'name' => 'Sezon 2026/27',
            'starts_at' => '2026-09-19',
            'ends_at' => '2026-11-21',
            'status' => 'active',
            'rounds' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('leagues')->orderBy('level')->get() as $league) {
            DB::table('season_leagues')->insert([
                'season_id' => $seasonId,
                'league_id' => $league->id,
                'promotion_places' => $league->level === 1 ? 0 : 4,
                'relegation_places' => $league->level === 11 ? 0 : 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $season = DB::table('seasons')->where('name', 'Sezon 2026/27')->where('status', 'active')->first();
        if ($season === null) {
            return;
        }

        DB::table('season_leagues')->where('season_id', $season->id)->delete();
        DB::table('seasons')->where('id', $season->id)->delete();
    }
};
