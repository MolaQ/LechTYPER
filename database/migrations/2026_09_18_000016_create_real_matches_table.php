<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_round_id')->nullable()->unique()->constrained('season_rounds')->nullOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('home_team');
            $table->string('away_team');
            $table->string('competition');
            $table->timestamps();
            $table->index(['season_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_matches');
    }
};
