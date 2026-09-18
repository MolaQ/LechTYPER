<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@lechtyper.local');
        $password = env('ADMIN_PASSWORD', 'admin123');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'role' => 'admin',
                'password' => Hash::make($password),
                'must_change_password' => false,
            ],
        );
    }
}
