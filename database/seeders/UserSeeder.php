<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Penjoki 1',
            'email' => 'penjoki1@gmail.com',
            'password' => 'password123',
            'role' => 'penjoki',
        ]);
    }
}
