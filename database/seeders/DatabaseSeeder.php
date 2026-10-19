<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        User::firstOrCreate(
            ['email' => 'admin@birthday.app'],
            [
                'name' => 'Admin Birthday App',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // Demo User
        User::firstOrCreate(
            ['email' => 'user@birthday.app'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );

        // Seed Templates & Categories
        $this->call([
            TemplateSeeder::class,
        ]);
    }
}
