<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonus_question_pool', function (Blueprint $table): void {
            $table->dropUnique(['type', 'question_text']);
            $table->string('type')->nullable()->change();
            $table->unique('question_text');
        });

        DB::table('bonus_question_pool')->update(['type' => null]);
    }

    public function down(): void
    {
        Schema::table('bonus_question_pool', function (Blueprint $table): void {
            $table->dropUnique(['question_text']);
            $table->string('type')->default('offensive')->change();
            $table->unique(['type', 'question_text']);
        });
    }
};
