<?php

namespace Database\Seeders;

use App\Models\League;
use Illuminate\Database\Seeder;

class LeagueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leagues = [
            ['Ekstraklasa', 'ekstraklasa', 1, false],
            ['I liga', 'i-liga', 2, false],
            ['II liga', 'ii-liga', 3, false],
            ['III liga', 'iii-liga', 4, false],
            ['IV liga', 'iv-liga', 5, false],
            ['V liga', 'v-liga', 6, false],
            ['Liga Okręgowa', 'liga-okregowa', 7, false],
            ['A klasa', 'a-klasa', 8, false],
            ['B klasa', 'b-klasa', 9, false],
            ['C klasa', 'c-klasa', 10, false],
            ['Liga podwórkowa', 'liga-podworkowa', 11, true],
        ];

        foreach ($leagues as [$name, $slug, $level, $isSwiss]) {
            League::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'level' => $level,
                    'is_swiss' => $isSwiss,
                ],
            );
        }
    }
}
