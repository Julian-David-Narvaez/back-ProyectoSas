<x-mail::message>
    # ¡Cita agendada exitosamente!

    Hola {{ $booking->customer_name }},

    Tu cita ha sido registrada correctamente. Aquí tienes los detalles:

    - **Negocio:** {{ $booking->service->business->name ?? '' }}
    - **Servicio:** {{ $booking->service->name ?? '' }}
    - **Fecha:** {{ \Carbon\Carbon::parse($booking->start_at)->format('d/m/Y') }}
    - **Hora:** {{ \Carbon\Carbon::parse($booking->start_at)->format('H:i') }}

    Si necesitas modificar o cancelar tu cita, por favor responde a este correo o comunícate con el negocio.

    Gracias por confiar en nosotros.<br>
    {{ config('app.name') }}
</x-mail::message>