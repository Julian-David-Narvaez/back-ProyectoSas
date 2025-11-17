<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ResendMailService;

class ResendTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resend:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el envío de correo usando Resend';

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
        
        $this->info('Probando envío de correo con Resend...');
        $this->info("Enviando a: {$email}");
        
        try {
            $result = $this->mailService->sendTestEmail($email);
            
            if ($result['success']) {
                $this->info("✅ Correo enviado exitosamente!");
                $this->info("ID del correo: " . $result['id']);
            } else {
                $this->error("❌ Error al enviar correo:");
                $this->error($result['error']);
            }
        } catch (\Exception $e) {
            $this->error("❌ Excepción capturada:");
            $this->error($e->getMessage());
        }
    }
}