<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('team_players')->delete();
    }

    public function down(): void
    {
        // Assignments are intentionally not restored because they were generated copies.
    }
};
