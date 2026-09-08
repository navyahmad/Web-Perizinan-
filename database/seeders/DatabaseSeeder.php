<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultPassword = env('SEED_USER_PASSWORD', 'password');

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => 'Administrator',
                'password' => Hash::make($defaultPassword),
                'role' => 'admin',
                'phone' => '081234567890',
            ]
        );

        User::updateOrCreate(
            ['email' => env('HRD_EMAIL', 'hrd@example.com')],
            [
                'name' => 'Tim HRD',
                'password' => Hash::make($defaultPassword),
                'role' => 'hrd',
                'phone' => '081234567891',
            ]
        );
    }
}
