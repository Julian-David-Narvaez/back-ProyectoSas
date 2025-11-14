@component('mail::message')
# ✅ ¡Cita Confirmada!

Hola **{{ $booking->customer_name }}**,

Tu cita ha sido registrada exitosamente. A continuación, los detalles:

@component('mail::panel')
## Detalles de tu Cita

**Negocio:** {{ $booking->service->business->name ?? 'N/A' }}
**Servicio:** {{ $booking->service->name ?? 'N/A' }}
**Duración:** {{ $booking->service->duration_minutes ?? 0 }} minutos
@if($booking->employee)
**Profesional:** {{ $booking->employee->name }}
@endif
**Fecha:** {{ \Carbon\Carbon::parse($booking->start_at)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
**Hora:** {{ \Carbon\Carbon::parse($booking->start_at)->format('h:i A') }}
**Hora de finalización:** {{ \Carbon\Carbon::parse($booking->end_at)->format('h:i A') }}
@endcomponent

@if($booking->service->business->phone)
📞 **Teléfono:** {{ $booking->service->business->phone }}
@endif
@if($booking->service->business->address)
📍 **Dirección:** {{ $booking->service->business->address }}
@endif

### Importante:
- Por favor, llega 5 minutos antes de tu cita
- Si necesitas cancelar o reprogramar, hazlo con al menos 24 horas de anticipación
- Guarda este correo como comprobante de tu reserva



Si tienes alguna pregunta, no dudes en contactarnos respondiendo este correo.

Gracias por confiar en nosotros,
**{{ $booking->service->business->name ?? config('app.name') }}**
@endcomponent