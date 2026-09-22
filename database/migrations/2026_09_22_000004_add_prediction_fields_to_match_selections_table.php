<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_selections', function (Blueprint $table): void {
            $table->unsignedTinyInteger('home_score')->nullable()->after('team_id');
            $table->unsignedTinyInteger('away_score')->nullable()->after('home_score');
            $table->unsignedTinyInteger('points_base')->default(0)->after('away_score');
            $table->unsignedTinyInteger('points_offensive')->default(0)->after('points_base');
            $table->unsignedTinyInteger('points_defensive_applied')->default(0)->after('points_offensive');
            $table->unsignedSmallInteger('total_points')->default(0)->after('points_defensive_applied');
        });
    }

    public function down(): void
    {
        Schema::table('match_selections', function (Blueprint $table): void {
            $table->dropColumn(['home_score', 'away_score', 'points_base', 'points_offensive', 'points_defensive_applied', 'total_points']);
        });
    }
};
