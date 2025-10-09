<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\Service;
use App\Models\WorkingHour;

class DemoServicesAndHoursSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where('business_name', 'Demo Barbería')->first();
        if (! $business) return;

        // Servicios
        $services = [
            ['name' => 'Corte básico', 'duration_minutes' => 30, 'price' => 15.00, 'is_active' => true],
            ['name' => 'Corte premium', 'duration_minutes' => 45, 'price' => 25.00, 'is_active' => true],
            ['name' => 'Afeitado', 'duration_minutes' => 20, 'price' => 10.00, 'is_active' => true]
        ];

        foreach ($services as $s) {
            Service::updateOrCreate([
                'business_id' => $business->id,
                'name' => $s['name']
            ], array_merge($s, ['business_id' => $business->id]));
        }

        // Horarios (Lunes a Viernes 09:00 - 17:00)
        for ($d = 1; $d <= 5; $d++) {
            WorkingHour::updateOrCreate([
                'business_id' => $business->id,
                'day_of_week' => $d
            ], [
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true
            ]);
        }
    }
}
