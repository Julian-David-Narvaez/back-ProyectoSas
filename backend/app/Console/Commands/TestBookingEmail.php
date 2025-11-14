<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Mail\BookingConfirmationMail;
use Illuminate\Support\Facades\Mail;

class TestBookingEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-booking {booking_id?} {--email=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un correo de prueba de confirmación de reserva';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bookingId = $this->argument('booking_id');
        $testEmail = $this->option('email');

        // Si no se proporciona ID, usar la última reserva
        if (!$bookingId) {
            $booking = Booking::latest()->first();
            
            if (!$booking) {
                $this->error('❌ No hay reservas en la base de datos.');
                $this->info('💡 Crea una reserva primero desde el frontend o usa el seeder.');
                return 1;
            }
            
            $this->info("📋 Usando la última reserva (ID: {$booking->id})");
        } else {
            $booking = Booking::find($bookingId);
            
            if (!$booking) {
                $this->error("❌ No se encontró la reserva con ID: {$bookingId}");
                return 1;
            }
        }

        // Mostrar información de la reserva
        $this->newLine();
        $this->info('📧 Información de la reserva:');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $booking->id],
                ['Cliente', $booking->customer_name],
                ['Email Original', $booking->customer_email],
                ['Servicio', $booking->service->name ?? 'N/A'],
                ['Negocio', $booking->service->business->name ?? 'N/A'],
                ['Fecha', $booking->start_at->format('d/m/Y H:i')],
                ['Estado', $booking->status],
            ]
        );

        // Determinar email de destino
        $destinationEmail = $testEmail ?? $booking->customer_email;
        
        $this->newLine();
        $this->info("📮 Enviando correo a: {$destinationEmail}");
        
        // Confirmar envío
        if ($testEmail) {
            $this->warn("⚠️  Usando email de prueba en lugar del email original del cliente");
        }
        
        if (!$this->confirm('¿Deseas continuar con el envío?', true)) {
            $this->info('❌ Envío cancelado.');
            return 0;
        }

        // Enviar correo
        try {
            Mail::to($destinationEmail)->send(new BookingConfirmationMail($booking));
            
            $this->newLine();
            $this->info('✅ ¡Correo enviado exitosamente!');
            $this->newLine();
            
            // Mostrar información adicional según la configuración
            $mailer = config('mail.default');
            
            if ($mailer === 'log') {
                $this->warn('⚠️  MAIL_MAILER está configurado en "log"');
                $this->info('📝 El correo fue guardado en: storage/logs/laravel.log');
                $this->info('💡 Para enviar correos reales, configura SMTP en tu archivo .env');
            } else {
                $this->info('📬 Configuración actual: ' . $mailer);
                $this->info('🔍 Revisa la bandeja de entrada (y spam) del email destino');
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error al enviar el correo:');
            $this->error($e->getMessage());
            $this->newLine();
            $this->warn('💡 Sugerencias:');
            $this->line('  - Verifica la configuración SMTP en tu archivo .env');
            $this->line('  - Revisa los logs en storage/logs/laravel.log');
            $this->line('  - Ejecuta: php artisan config:clear');
            
            return 1;
        }
    }
}
