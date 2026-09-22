<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('lech_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('opponent');
            $table->boolean('lech_home')->default(true);
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('result_home')->nullable();
            $table->unsignedSmallInteger('result_away')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
            $table->index(['scheduled_at', 'status']);
        });

        Schema::create('h2h_fixtures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opponent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('lech_matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('round_number')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'opponent_id', 'match_id']);
        });

        Schema::create('bonus_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained('lech_matches')->cascadeOnDelete();
            $table->string('type');
            $table->text('question_text');
            $table->boolean('correct_answer')->nullable();
            $table->timestamps();
            $table->index(['match_id', 'type']);
        });

        Schema::create('predictions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('lech_matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->unsignedTinyInteger('points_base')->default(0);
            $table->unsignedTinyInteger('points_offensive')->default(0);
            $table->unsignedTinyInteger('points_defensive_applied')->default(0);
            $table->unsignedSmallInteger('total_points')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'match_id']);
        });

        Schema::create('user_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bonus_question_id')->constrained()->cascadeOnDelete();
            $table->boolean('answer')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'bonus_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_answers');
        Schema::dropIfExists('predictions');
        Schema::dropIfExists('bonus_questions');
        Schema::dropIfExists('h2h_fixtures');
        Schema::dropIfExists('lech_matches');
        Schema::dropIfExists('competitions');
    }
};
