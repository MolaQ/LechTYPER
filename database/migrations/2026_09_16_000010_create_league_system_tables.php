<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leagues', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('level')->unique();
            $table->boolean('is_swiss')->default(false);
            $table->timestamps();
        });

        Schema::create('seasons', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->default('planned');
            $table->unsignedTinyInteger('rounds')->default(9);
            $table->timestamps();
        });

        Schema::create('season_leagues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('promotion_places')->default(0);
            $table->unsignedTinyInteger('relegation_places')->default(0);
            $table->timestamps();
            $table->unique(['season_id', 'league_id']);
        });

        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('season_teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->nullable();
            $table->unsignedTinyInteger('played')->default(0);
            $table->unsignedTinyInteger('wins')->default(0);
            $table->unsignedTinyInteger('draws')->default(0);
            $table->unsignedTinyInteger('losses')->default(0);
            $table->unsignedSmallInteger('points')->default(0);
            $table->integer('score_for')->default(0);
            $table->integer('score_against')->default(0);
            $table->timestamps();
            $table->unique(['season_league_id', 'team_id']);
        });

        Schema::create('players', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('team_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->timestamp('injury_until')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'player_id']);
        });

        Schema::create('matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_league_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('round_number');
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('status')->default('scheduled');
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->timestamps();
            $table->index(['season_league_id', 'round_number']);
        });

        Schema::create('match_selections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['match_id', 'team_id']);
        });

        Schema::create('match_selection_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_selection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->decimal('points', 8, 2)->nullable();
            $table->timestamps();
            $table->unique(['match_selection_id', 'player_id']);
        });

        Schema::create('player_match_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->decimal('points', 8, 2)->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['match_id', 'player_id']);
        });

        $leagues = [
            ['Ekstraklasa', 'ekstraklasa', 1, false, 0, 4],
            ['I liga', 'i-liga', 2, false, 4, 4],
            ['II liga', 'ii-liga', 3, false, 4, 4],
            ['III liga', 'iii-liga', 4, false, 4, 4],
            ['IV liga', 'iv-liga', 5, false, 4, 4],
            ['V liga', 'v-liga', 6, false, 4, 4],
            ['Liga Okręgowa', 'liga-okregowa', 7, false, 4, 4],
            ['A klasa', 'a-klasa', 8, false, 4, 4],
            ['B klasa', 'b-klasa', 9, false, 4, 4],
            ['C klasa', 'c-klasa', 10, false, 4, 4],
            ['Liga podwórkowa', 'liga-podworkowa', 11, true, 4, 0],
        ];

        foreach ($leagues as [$name, $slug, $level, $isSwiss, $promotionPlaces, $relegationPlaces]) {
            DB::table('leagues')->insert([
                'name' => $name,
                'slug' => $slug,
                'level' => $level,
                'is_swiss' => $isSwiss,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $seasonId = DB::table('seasons')->insertGetId([
            'name' => 'Sezon 2026/27',
            'starts_at' => '2026-09-19',
            'ends_at' => '2026-11-21',
            'status' => 'active',
            'rounds' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('leagues')->get() as $league) {
            DB::table('season_leagues')->insert([
                'season_id' => $seasonId,
                'league_id' => $league->id,
                'promotion_places' => $league->level === 1 ? 0 : 4,
                'relegation_places' => $league->level === 11 ? 0 : 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('player_match_stats');
        Schema::dropIfExists('match_selection_players');
        Schema::dropIfExists('match_selections');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('team_players');
        Schema::dropIfExists('players');
        Schema::dropIfExists('season_teams');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('season_leagues');
        Schema::dropIfExists('seasons');
        Schema::dropIfExists('leagues');
    }
};
