<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $booking;

    /**
     * Create a new message instance.
     */
    public function __construct($booking)
    {
        // IMPORTANTE: Cargar las relaciones necesarias
        $this->booking = $booking->load('service.business', 'employee');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Confirmación de tu Cita - ' . ($this->booking->service->business->name ?? 'Citas'),
            from: config('services.resend.default_from.email', 'onboarding@resend.dev'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: $this->generateHtmlContent(),
        );
    }

    /**
     * Generate HTML content for the email
     */
    private function generateHtmlContent(): string
    {
        $businessName = $this->booking->service->business->name ?? 'Sistema de Citas';
        $serviceName = $this->booking->service->name ?? 'Servicio';
        $employeeName = $this->booking->employee->name ?? 'Personal';
        $date = $this->booking->booking_date;
        $time = $this->booking->booking_time;
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4F46E5; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 20px; background: #f9f9f9; }
                .details { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #4F46E5; }
                .footer { text-align: center; padding: 20px; color: #666; border-radius: 0 0 8px 8px; }
                .highlight { color: #4F46E5; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ ¡Cita Confirmada!</h1>
                </div>
                <div class='content'>
                    <p>¡Hola!</p>
                    <p>Tu cita ha sido <span class='highlight'>confirmada exitosamente</span>. Aquí tienes todos los detalles:</p>
                    
                    <div class='details'>
                        <p><strong>🏢 Negocio:</strong> {$businessName}</p>
                        <p><strong>🛠️ Servicio:</strong> {$serviceName}</p>
                        <p><strong>👤 Profesional:</strong> {$employeeName}</p>
                        <p><strong>📅 Fecha:</strong> {$date}</p>
                        <p><strong>🕐 Hora:</strong> {$time}</p>
                    </div>
                    
                    <p><strong>Recordatorio importante:</strong></p>
                    <ul>
                        <li>Por favor, llega 10 minutos antes de tu cita</li>
                        <li>Si necesitas cancelar o reprogramar, contáctanos lo antes posible</li>
                        <li>Trae cualquier documentación que puedas necesitar</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p><strong>¡Gracias por elegirnos!</strong></p>
                    <p>{$businessName}</p>
                    <p style='font-size: 12px; color: #999;'>Este es un correo automático enviado desde nuestro sistema de citas.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}