<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestEmailCommand extends Command
{
    protected $signature = 'email:test {email : El email destino}';
    protected $description = 'Envía un correo de prueba para verificar la configuración';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("📧 Enviando correo de prueba a: {$email}");
        
        try {
            Mail::raw('✅ ¡Configuración de correo funcionando correctamente! Este es un mensaje de prueba desde tu aplicación SaaS Citas.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('🔧 Prueba de Configuración de Correo - SaaS Citas');
            });
            
            $this->info('✅ Correo enviado exitosamente!');
            $this->info('📬 Revisa la bandeja de entrada de: ' . $email);
            
            Log::info('Correo de prueba enviado', ['email' => $email]);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error al enviar correo: ' . $e->getMessage());
            Log::error('Error en correo de prueba', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}