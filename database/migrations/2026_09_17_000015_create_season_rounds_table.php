<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('round_number');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('real_match_at')->nullable();
            $table->string('real_home_team')->nullable();
            $table->string('real_away_team')->nullable();
            $table->string('competition')->nullable();
            $table->timestamps();
            $table->unique(['season_id', 'round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_rounds');
    }
};
