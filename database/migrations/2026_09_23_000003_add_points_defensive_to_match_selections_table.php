<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('match_selections', function (Blueprint $table): void {
            $table->unsignedTinyInteger('points_defensive')->default(0)->after('points_offensive');
        });
    }

    public function down(): void
    {
        Schema::table('match_selections', function (Blueprint $table): void {
            $table->dropColumn('points_defensive');
        });
    }
};
