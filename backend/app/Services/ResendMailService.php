<?php

namespace App\Services;

use Resend\Client as ResendClient;
use Illuminate\Support\Facades\Log;
use Exception;

class ResendMailService
{
    protected ResendClient $resend;
    protected array $defaultFrom;

    public function __construct(ResendClient $resend)
    {
        $this->resend = $resend;
        $this->defaultFrom = config('services.resend.default_from', [
            'email' => 'onboarding@resend.dev',
            'name' => 'Sistema de Citas'
        ]);
    }

    /**
     * Enviar email usando Resend API directamente
     */
    public function sendEmail(array $params): array
    {
        try {
            // Validar parámetros requeridos
            $this->validateEmailParams($params);

            // Preparar datos para Resend
            $emailData = $this->prepareEmailData($params);

            // Enviar email
            $result = $this->resend->emails->send($emailData);

            Log::info('Email enviado exitosamente via Resend', [
                'id' => $result->id ?? null,
                'to' => $params['to'],
                'subject' => $params['subject']
            ]);

            return [
                'success' => true,
                'id' => $result->id ?? null,
                'message' => 'Email enviado exitosamente'
            ];

        } catch (Exception $e) {
            Log::error('Error enviando email con Resend', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email de confirmación de cita
     */
    public function sendBookingConfirmation($booking, $recipientEmail = null): array
    {
        $email = $recipientEmail ?? $booking->customer_email;
        
        return $this->sendEmail([
            'to' => [$email],
            'subject' => '✅ Confirmación de tu Cita - ' . ($booking->service->business->name ?? 'Sistema de Citas'),
            'html' => $this->generateBookingConfirmationHtml($booking),
        ]);
    }

    /**
     * Enviar email simple de prueba
     */
    public function sendTestEmail(string $to, string $subject = 'Prueba desde Resend'): array
    {
        return $this->sendEmail([
            'to' => [$to],
            'subject' => $subject,
            'html' => '<h1>¡Hola!</h1><p>Este es un email de prueba enviado desde Resend.</p><p>El sistema está funcionando correctamente.</p>'
        ]);
    }

    /**
     * Validar parámetros del email
     */
    protected function validateEmailParams(array $params): void
    {
        if (empty($params['to'])) {
            throw new Exception('El campo "to" es requerido');
        }

        if (empty($params['subject'])) {
            throw new Exception('El campo "subject" es requerido');
        }

        if (empty($params['html']) && empty($params['text'])) {
            throw new Exception('Se requiere al menos "html" o "text"');
        }
    }

    /**
     * Preparar datos para enviar a Resend
     */
    protected function prepareEmailData(array $params): array
    {
        return [
            'from' => $params['from'] ?? $this->formatFromAddress(),
            'to' => (array) $params['to'],
            'subject' => $params['subject'],
            'html' => $params['html'] ?? null,
            'text' => $params['text'] ?? null,
            'reply_to' => $params['reply_to'] ?? null,
            'cc' => $params['cc'] ?? null,
            'bcc' => $params['bcc'] ?? null,
            'tags' => $params['tags'] ?? null,
        ];
    }

    /**
     * Formatear dirección de remitente
     */
    protected function formatFromAddress(): string
    {
        $name = $this->defaultFrom['name'] ?? 'Sistema de Citas';
        $email = $this->defaultFrom['email'] ?? 'onboarding@resend.dev';
        
        return "{$name} <{$email}>";
    }

    /**
     * Generar HTML para confirmación de cita
     */
    protected function generateBookingConfirmationHtml($booking): string
    {
        $businessName = $booking->service->business->name ?? 'Sistema de Citas';
        $serviceName = $booking->service->name ?? 'Servicio';
        $employeeName = $booking->employee->name ?? 'Personal';
        $date = \Carbon\Carbon::parse($booking->start_at)->format('Y-m-d');
        $time = \Carbon\Carbon::parse($booking->start_at)->format('H:i');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .details { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .footer { text-align: center; padding: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Cita Confirmada</h1>
                </div>
                <div class='content'>
                    <p>Hola,</p>
                    <p>Tu cita ha sido confirmada exitosamente. Aquí tienes los detalles:</p>
                    
                    <div class='details'>
                        <strong>🏢 Negocio:</strong> {$businessName}<br>
                        <strong>🛠️ Servicio:</strong> {$serviceName}<br>
                        <strong>👤 Profesional:</strong> {$employeeName}<br>
                        <strong>📅 Fecha:</strong> {$date}<br>
                        <strong>🕐 Hora:</strong> {$time}
                    </div>
                    
                    <p>Por favor, llega 10 minutos antes de tu cita.</p>
                    <p>Si necesitas cancelar o reprogramar, contacta con nosotros lo antes posible.</p>
                </div>
                <div class='footer'>
                    <p>Gracias por elegirnos</p>
                    <p>{$businessName}</p>
                </div>
            </div>
        </body>
        </html>";
    }
}