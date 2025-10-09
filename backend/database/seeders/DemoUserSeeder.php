<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'admin@demo.com'
        ], [
            'name' => 'Admin Demo',
            'password' => Hash::make('password123'),
            'phone' => '3000000000'
        ]);
    }
}
