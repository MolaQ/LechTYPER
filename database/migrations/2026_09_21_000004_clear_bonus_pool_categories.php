<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bonus_question_pool')->update(['type' => null]);
    }

    public function down(): void
    {
        // The neutral pool intentionally has no category to restore.
    }
};
