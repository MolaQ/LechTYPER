<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('league_rounds', function (Blueprint $table): void {
            $table->unsignedTinyInteger('real_score_home')->nullable()->after('real_away_team');
            $table->unsignedTinyInteger('real_score_away')->nullable()->after('real_score_home');
        });
    }

    public function down(): void
    {
        Schema::table('league_rounds', function (Blueprint $table): void {
            $table->dropColumn(['real_score_home', 'real_score_away']);
        });
    }
};
