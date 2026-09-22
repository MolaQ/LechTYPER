<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_selection_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_selection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_round_bonus_question_id')->constrained()->cascadeOnDelete();
            $table->boolean('answer')->nullable();
            $table->timestamps();
            $table->unique(['match_selection_id', 'league_round_bonus_question_id'], 'match_selection_answers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_selection_answers');
    }
};
