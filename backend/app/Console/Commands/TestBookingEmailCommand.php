<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use App\Models\Employee;
use App\Mail\BookingConfirmationMail;
use App\Services\ResendMailService;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class TestBookingEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:test-email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el envío de correo de confirmación de reserva';

    protected ResendMailService $mailService;

    public function __construct(ResendMailService $mailService)
    {
        parent::__construct();
        $this->mailService = $mailService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('Creando reserva de prueba...');
        
        // Crear datos de prueba
        $testBooking = $this->createTestBooking($email);
        
        $this->info('Enviando correo de confirmación...');
        $this->info("Destinatario: {$email}");
        
        try {
            // Probar con Laravel Mail
            $this->info('🧪 Probando con Laravel Mail...');
            Mail::to($email)->send(new BookingConfirmationMail($testBooking));
            $this->info("✅ Laravel Mail enviado exitosamente!");
            
            // También probar con ResendMailService directamente
            $this->info('🧪 Probando con ResendMailService...');
            $result = $this->mailService->sendBookingConfirmation($testBooking, $email);
            
            if ($result['success']) {
                $this->info("✅ ResendMailService enviado exitosamente!");
                $this->info("ID del correo: " . ($result['id'] ?? 'N/A'));
            } else {
                $this->error("❌ Error con ResendMailService:");
                $this->error($result['error']);
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Excepción capturada:");
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
    
    /**
     * Crear reserva de prueba
     */
    protected function createTestBooking($email): Booking
    {
        // Crear objetos ficticios para la prueba
        $business = new Business([
            'id' => 1,
            'name' => 'Salón de Belleza Test',
            'description' => 'Un salón de belleza de prueba',
        ]);
        
        $service = new Service([
            'id' => 1,
            'name' => 'Corte de Cabello',
            'description' => 'Corte de cabello profesional',
            'duration_minutes' => 60,
            'price' => 50000,
        ]);
        
        $employee = new Employee([
            'id' => 1,
            'name' => 'Ana García',
            'email' => 'ana@salon.com',
        ]);
        
        $booking = new Booking([
            'id' => 999,
            'business_id' => 1,
            'service_id' => 1,
            'employee_id' => 1,
            'customer_name' => 'Cliente Test',
            'customer_email' => $email,
            'start_at' => Carbon::now()->addDays(1)->setTime(14, 0),
            'end_at' => Carbon::now()->addDays(1)->setTime(15, 0),
            'status' => 'confirmed',
        ]);
        
        // Establecer relaciones manualmente
        $service->business = $business;
        $booking->service = $service;
        $booking->employee = $employee;
        
        return $booking;
    }
}