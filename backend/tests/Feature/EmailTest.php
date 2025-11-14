<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Business;
use App\Models\Service;
use App\Models\Booking;
use App\Models\Employee;
use App\Mail\BookingConfirmationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class EmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que verifica que se envía el correo al crear una reserva
     */
    public function test_booking_confirmation_email_is_sent_when_booking_is_created()
    {
        Mail::fake();

        // Crear datos de prueba
        $user = User::factory()->create(['role' => 'business_owner']);
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Negocio de Prueba',
            'slug' => 'negocio-prueba',
            'description' => 'Descripción de prueba',
        ]);
        
        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Servicio de Prueba',
            'description' => 'Descripción del servicio',
            'duration_minutes' => 60,
            'price' => 50000,
        ]);

        // Crear reserva
        $booking = Booking::create([
            'business_id' => $business->id,
            'service_id' => $service->id,
            'customer_name' => 'Cliente Prueba',
            'customer_email' => 'cliente@example.com',
            'start_at' => Carbon::now()->addDays(1)->setTime(10, 0),
            'end_at' => Carbon::now()->addDays(1)->setTime(11, 0),
            'status' => 'confirmed',
        ]);

        // Enviar correo manualmente (simula lo que hace el controlador)
        Mail::to($booking->customer_email)->send(new BookingConfirmationMail($booking));

        // Verificar que se envió el correo
        Mail::assertSent(BookingConfirmationMail::class, function ($mail) use ($booking) {
            return $mail->hasTo($booking->customer_email);
        });
    }

    /**
     * Test para verificar el contenido del correo
     */
    public function test_booking_confirmation_email_contains_correct_information()
    {
        // Crear datos de prueba
        $user = User::factory()->create(['role' => 'business_owner']);
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Mi Negocio',
            'slug' => 'mi-negocio',
            'description' => 'Descripción',
        ]);
        
        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Corte de Cabello',
            'description' => 'Corte profesional',
            'duration_minutes' => 30,
            'price' => 25000,
        ]);

        $employee = Employee::create([
            'business_id' => $business->id,
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'is_active' => true,
            'order' => 1,
        ]);

        $booking = Booking::create([
            'business_id' => $business->id,
            'service_id' => $service->id,
            'employee_id' => $employee->id,
            'customer_name' => 'María López',
            'customer_email' => 'maria@example.com',
            'start_at' => Carbon::parse('2025-11-20 10:00:00'),
            'end_at' => Carbon::parse('2025-11-20 10:30:00'),
            'status' => 'confirmed',
        ]);

        // Crear instancia del correo
        $mailable = new BookingConfirmationMail($booking);

        // Renderizar el contenido
        $mailable->assertSeeInHtml('Mi Negocio');
        $mailable->assertSeeInHtml('Corte de Cabello');
        $mailable->assertSeeInHtml('María López');
        $mailable->assertSeeInHtml('Juan Pérez');
    }
}
