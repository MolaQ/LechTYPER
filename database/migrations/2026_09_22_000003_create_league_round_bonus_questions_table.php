<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_round_bonus_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('league_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pool_question_id')->nullable()->constrained('bonus_question_pool')->nullOnDelete();
            $table->string('type');
            $table->text('question_text');
            $table->boolean('correct_answer')->nullable();
            $table->timestamps();
            $table->unique(['league_round_id', 'pool_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_round_bonus_questions');
    }
};
