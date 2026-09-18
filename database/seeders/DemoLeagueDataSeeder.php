<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoLeagueDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $user = User::query()->firstOrCreate(
                ['email' => "demo{$i}@lechtyper.local"],
                [
                    'name' => "Demo Kibic {$i}",
                    'role' => 'user',
                    'password' => Hash::make('demo123'),
                ],
            );

            Team::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['name' => "Demo Drużyna {$i}"],
            );
        }
    }
}
