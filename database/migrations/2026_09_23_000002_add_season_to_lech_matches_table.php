<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lech_matches', function (Blueprint $table): void {
            $table->foreignId('season_id')->nullable()->after('competition_id')->constrained()->nullOnDelete();
            $table->index(['season_id', 'scheduled_at']);
        });

        $activeSeasonId = DB::table('seasons')->where('status', 'active')->value('id');
        if ($activeSeasonId !== null) {
            DB::table('lech_matches')->whereNull('season_id')->update(['season_id' => $activeSeasonId]);
        }
    }

    public function down(): void
    {
        Schema::table('lech_matches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('season_id');
        });
    }
};
