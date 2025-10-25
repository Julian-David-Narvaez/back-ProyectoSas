<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Service;
use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    /**
     * Obtener disponibilidad para un servicio en una fecha específica
     */
    public function getAvailability(Request $request, $businessId)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $business = Business::with('schedules')->findOrFail($businessId);
        $service = Service::findOrFail($request->service_id);
        
        // Verificar que el servicio pertenece al negocio
        if ($service->business_id != $businessId) {
            return response()->json(['message' => 'Servicio no válido'], 400);
        }

        $date = Carbon::parse($request->date);
        $weekday = $date->dayOfWeek; // 0 = Domingo, 6 = Sábado

        // Obtener horario del día
        $schedule = $business->schedules()->where('weekday', $weekday)->first();

        if (!$schedule) {
            return response()->json([
                'available_slots' => [],
                'message' => 'No hay atención este día'
            ]);
        }

        // Generar slots disponibles
        $slots = $this->generateTimeSlots(
            $schedule->start_time,
            $schedule->end_time,
            $service->duration_minutes
        );

        // Obtener reservas existentes para ese día
        $existingBookings = Booking::where('business_id', $businessId)
            ->whereDate('start_at', $date->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->get();

        // Filtrar slots ocupados
        $availableSlots = array_filter($slots, function($slot) use ($existingBookings, $date, $service) {
            $slotStart = Carbon::parse($date->format('Y-m-d') . ' ' . $slot);
            $slotEnd = $slotStart->copy()->addMinutes($service->duration_minutes);

            foreach ($existingBookings as $booking) {
                $bookingStart = Carbon::parse($booking->start_at);
                $bookingEnd = Carbon::parse($booking->end_at);

                // Verificar conflicto: si hay overlap
                if ($slotStart < $bookingEnd && $slotEnd > $bookingStart) {
                    return false;
                }
            }

            return true;
        });

        return response()->json([
            'available_slots' => array_values($availableSlots),
            'schedule' => [
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ],
        ]);
    }

    /**
     * Crear una reserva
     */
    public function store(Request $request)
    {
        $request->validate([
        'business_id' => 'required|exists:businesses,id',
        'service_id' => 'required|exists:services,id',
        'customer_name' => 'required|string|max:255|min:3',
        'customer_email' => 'required|email|max:255',
        'date' => 'required|date|after_or_equal:today',
        'time' => 'required|date_format:H:i',
    ], [
        'customer_name.min' => 'El nombre debe tener al menos 3 caracteres',
        'customer_email.email' => 'Por favor ingresa un email válido',
        'date.after_or_equal' => 'No puedes reservar en fechas pasadas',
    ]);

    $service = Service::findOrFail($request->service_id);
    
    // Verificar que el servicio pertenece al negocio
    if ($service->business_id != $request->business_id) {
        return response()->json(['message' => 'Servicio no válido'], 400);
    }

    // CORRECCIÓN: Combinar fecha y hora correctamente
    $startAt = Carbon::parse($request->date . ' ' . $request->time);
    $endAt = $startAt->copy()->addMinutes($service->duration_minutes);

    // Log para debug
    Log::info('Creando reserva', [
        'date' => $request->date,
        'time' => $request->time,
        'start_at' => $startAt->toDateTimeString(),
        'end_at' => $endAt->toDateTimeString(),
    ]);

    // Verificar disponibilidad
    $conflict = Booking::where('business_id', $request->business_id)
        ->where('status', 'confirmed')
        ->where(function($query) use ($startAt, $endAt) {
            $query->where(function($q) use ($startAt, $endAt) {
                $q->where('start_at', '<', $endAt)
                  ->where('end_at', '>', $startAt);
            });
        })
        ->exists();

    if ($conflict) {
        return response()->json([
            'message' => 'Este horario ya no está disponible'
        ], 409);
    }

    $booking = Booking::create([
        'business_id' => $request->business_id,
        'service_id' => $service->id,
        'user_id' => auth()->id(), // Puede ser null para clientes sin cuenta
        'customer_name' => $request->customer_name,
        'customer_email' => $request->customer_email,
        'start_at' => $startAt,
        'end_at' => $endAt,
        'status' => 'confirmed',
    ]);

    return response()->json([
        'message' => 'Reserva creada exitosamente',
        'booking' => $booking->load('service')
    ], 201);
}

    /**
     * Listar reservas de un negocio (para admin)
     */
    public function index($businessId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $bookings = Booking::with('service')
            ->where('business_id', $businessId)
            ->orderBy('start_at', 'desc')
            ->get();

        return response()->json($bookings);
    }

    /**
     * Actualizar estado de reserva
     */
    public function update(Request $request, $businessId, $bookingId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $booking = Booking::where('business_id', $businessId)
            ->findOrFail($bookingId);

        $request->validate([
            'status' => 'sometimes|in:confirmed,cancelled,completed',
            'start_at' => 'sometimes|date',
            'end_at' => 'sometimes|date|after:start_at',
        ]);

        $booking->update($request->all());

        return response()->json($booking->load('service'));
    }

    /**
     * Cancelar reserva
     */
    public function cancel($businessId, $bookingId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $booking = Booking::where('business_id', $businessId)
            ->findOrFail($bookingId);

        $booking->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Reserva cancelada',
            'booking' => $booking
        ]);
    }

    /**
     * Generar slots de tiempo
     */
    private function generateTimeSlots($startTime, $endTime, $duration)
    {
        $slots = [];
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $interval = 15; // Intervalo de 15 minutos

        while ($start->copy()->addMinutes($duration) <= $end) {
            $slots[] = $start->format('H:i');
            $start->addMinutes($interval);
        }

        return $slots;
    }
}