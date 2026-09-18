<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_positions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_league_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bot_name')->default('Chłopaki z orlika');
            $table->unsignedInteger('inherited_points')->default(0);
            $table->timestamps();
            $table->unique(['season_league_id', 'position']);
            $table->unique(['season_league_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_positions');
    }
};
