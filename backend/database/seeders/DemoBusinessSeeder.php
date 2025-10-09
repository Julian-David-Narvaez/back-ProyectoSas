<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\User;

class DemoBusinessSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@demo.com')->first();
        if (! $user) return;

        $business = Business::updateOrCreate([
            'business_name' => 'Demo Barbería',
            'user_id' => $user->id
        ], [
            'description' => 'Negocio demo para reservas',
            'address' => 'Calle Demo 123',
            'phone' => '3000000000',
            'email' => 'demo@barberia.com',
            'is_active' => true
        ]);

        // Crear página por defecto si no existe
        if (! $business->page) {
            $business->page()->create([
                'content' => ['sections' => []],
                'theme_config' => ['primaryColor' => '#3B82F6'],
                'is_published' => true
            ]);
        }
    }
}
