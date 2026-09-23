<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('season_teams', function (Blueprint $table): void {
            $table->unsignedSmallInteger('bonus_points')->default(0)->after('points');
        });
    }

    public function down(): void
    {
        Schema::table('season_teams', function (Blueprint $table): void {
            $table->dropColumn('bonus_points');
        });
    }
};
