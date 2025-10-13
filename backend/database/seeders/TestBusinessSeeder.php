<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Business;
use App\Models\Service;
use App\Models\Schedule;
use Illuminate\Support\Facades\Hash;

class TestBusinessSeeder extends Seeder
{
    public function run()
    {
        // Buscar o crear usuario admin
        $admin = User::firstOrCreate(
            ['email' => 'admins@test.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Crear negocio
        $business = Business::create([
            'user_id' => $admin->id,
            'name' => 'Barbería El Estilo',
            'slug' => 'barberia-el-estilo',
            'description' => 'La mejor barbería de la ciudad. Ofrecemos cortes modernos y clásicos.',
        ]);

        // Crear página
        $page = $business->page()->create([
            'template' => 'default',
        ]);

        // Crear bloques
        $page->blocks()->createMany([
            [
                'type' => 'hero',
                'order' => 1,
                'content' => [
                    'title' => 'Barbería El Estilo',
                    'subtitle' => 'Reserva tu cita en línea de forma rápida y sencilla',
                    'image_url' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=1200',
                ]
            ],
            [
                'type' => 'services',
                'order' => 2,
                'content' => [
                    'title' => 'Nuestros Servicios',
                ]
            ],
            [
                'type' => 'about',
                'order' => 3,
                'content' => [
                    'title' => 'Sobre Nosotros',
                    'text' => 'Con más de 10 años de experiencia, ofrecemos los mejores servicios de barbería. Nuestro equipo de profesionales está capacitado en las últimas tendencias y técnicas.',
                ]
            ],
            [
                'type' => 'contact',
                'order' => 4,
                'content' => [
                    'title' => 'Contáctanos',
                    'phone' => '+57 300 123 4567',
                    'email' => 'contacto@barberiaelestilo.com',
                    'address' => 'Calle 10 #5-20, Neiva',
                ]
            ],
        ]);

        // Crear servicios
        $services = [
            [
                'name' => 'Corte Clásico',
                'duration_minutes' => 30,
                'price' => 15000,
                'image_url' => 'https://images.unsplash.com/photo-1622286342621-4bd786c2447c?w=800',
                'order' => 1,
            ],
            [
                'name' => 'Corte + Barba',
                'duration_minutes' => 45,
                'price' => 25000,
                'image_url' => 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?w=800',
                'order' => 2,
            ],
            [
                'name' => 'Afeitado Tradicional',
                'duration_minutes' => 30,
                'price' => 18000,
                'image_url' => 'https://images.unsplash.com/photo-1585747860715-2ba37e788b70?w=800',
                'order' => 3,
            ],
            [
                'name' => 'Corte Degradado',
                'duration_minutes' => 40,
                'price' => 20000,
                'image_url' => 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?w=800',
                'order' => 4,
            ],
        ];

        foreach ($services as $serviceData) {
            $business->services()->create($serviceData);
        }

        // Crear horarios (Lunes a Viernes)
        for ($day = 1; $day <= 5; $day++) {
            $business->schedules()->create([
                'weekday' => $day,
                'start_time' => '09:00',
                'end_time' => '18:00',
            ]);
        }

        // Sábado medio día
        $business->schedules()->create([
            'weekday' => 6,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);

        $this->command->info('Negocio de prueba creado exitosamente!');
        $this->command->info('Slug: barberia-el-estilo');
        $this->command->info('URL: http://localhost:5173/barberia-el-estilo');
    }
}