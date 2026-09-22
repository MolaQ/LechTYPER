<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_question_pool', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->text('question_text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['type', 'question_text']);
        });

        Schema::table('bonus_questions', function (Blueprint $table): void {
            $table->foreignId('pool_question_id')->nullable()->after('match_id')->constrained('bonus_question_pool')->nullOnDelete();
            $table->unique(['match_id', 'pool_question_id']);
        });
    }

    public function down(): void
    {
        Schema::table('bonus_questions', function (Blueprint $table): void {
            $table->dropUnique(['match_id', 'pool_question_id']);
            $table->dropConstrainedForeignId('pool_question_id');
        });

        Schema::dropIfExists('bonus_question_pool');
    }
};
