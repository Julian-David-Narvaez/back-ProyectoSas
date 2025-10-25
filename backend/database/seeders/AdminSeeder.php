<?php

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@test.com'], // <- verifica si ya existe
            [
                'name' => 'Admin',
                'password' => Hash::make('password'), // o el password que uses
                'role' => 'admin',
            ]
        );
    }
}