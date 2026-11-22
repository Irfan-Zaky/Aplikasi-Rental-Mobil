<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's admin account.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'octaimub@gmail.com'],
            [
                'name' => 'octavia',
                'username' => 'octavia',
                'password' => Hash::make('ketuakita'),
                'email_verified_at' => now(),
                'role' => 'admin',
            ],
        );
    }
}
