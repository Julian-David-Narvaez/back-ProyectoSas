<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Service;
use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationMail;

class BookingController extends Controller
{
    /**
     * Obtener disponibilidad para un servicio en una fecha específica
     */
    public function getAvailability(Request $request, $businessId)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'employee_id' => 'nullable|exists:employees,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $business = Business::with(['schedules', 'employees'])->findOrFail($businessId);
        $service = Service::findOrFail($request->service_id);
        
        // Verificar que el servicio pertenece al negocio
        if ($service->business_id != $businessId) {
            return response()->json(['message' => 'Servicio no válido'], 400);
        }

        $date = Carbon::parse($request->date);
        $weekday = $date->dayOfWeek; // 0 = Domingo, 6 = Sábado
        $employeeId = $request->employee_id;

        // Obtener horario del día
        // Primero intentar buscar horario específico del empleado si se especifica
        $schedule = null;
        
        if ($employeeId) {
            // Buscar horario específico del empleado
            $schedule = $business->schedules()
                ->where('weekday', $weekday)
                ->where('employee_id', $employeeId)
                ->first();
        }
        
        // Si no se encontró horario específico (o no se especificó empleado), buscar horario general
        if (!$schedule) {
            $schedule = $business->schedules()
                ->where('weekday', $weekday)
                ->whereNull('employee_id')
                ->first();
        }

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

        // Determinar la lógica de disponibilidad según el caso
        if ($employeeId) {
            // Caso: Se seleccionó un empleado específico
            // Verificar solo la disponibilidad de ese empleado
            $existingBookings = Booking::where('business_id', $businessId)
                ->whereDate('start_at', $date->format('Y-m-d'))
                ->where('status', 'confirmed')
                ->where('employee_id', $employeeId)
                ->get();

            $availableSlots = array_filter($slots, function($slot) use ($existingBookings, $date, $service) {
                $slotStart = Carbon::parse($date->format('Y-m-d') . ' ' . $slot);
                $slotEnd = $slotStart->copy()->addMinutes($service->duration_minutes);

                foreach ($existingBookings as $booking) {
                    $bookingStart = Carbon::parse($booking->start_at);
                    $bookingEnd = Carbon::parse($booking->end_at);

                    if ($slotStart < $bookingEnd && $slotEnd > $bookingStart) {
                        return false;
                    }
                }

                return true;
            });
        } else if (!$schedule->employee_id) {
            // Caso: No se seleccionó empleado y el horario es general
            // Verificar disponibilidad considerando todos los empleados activos
            $activeEmployees = $business->employees()->where('is_active', true)->get();
            $employeeCount = $activeEmployees->count();
            
            if ($employeeCount === 0) {
                // Si no hay empleados, verificar solo que no haya reservas en ese horario general
                $existingBookings = Booking::where('business_id', $businessId)
                    ->whereDate('start_at', $date->format('Y-m-d'))
                    ->where('status', 'confirmed')
                    ->get();
            } else {
                // Si hay empleados, verificar que al menos uno esté disponible en cada slot
                $existingBookings = Booking::where('business_id', $businessId)
                    ->whereDate('start_at', $date->format('Y-m-d'))
                    ->where('status', 'confirmed')
                    ->get();
            }

            // Filtrar slots: un slot está disponible si hay al menos un empleado libre
            $availableSlots = array_filter($slots, function($slot) use ($existingBookings, $date, $service, $employeeCount) {
                $slotStart = Carbon::parse($date->format('Y-m-d') . ' ' . $slot);
                $slotEnd = $slotStart->copy()->addMinutes($service->duration_minutes);

                if ($employeeCount === 0) {
                    // Sin empleados: verificar que no haya ninguna reserva en este slot
                    foreach ($existingBookings as $booking) {
                        $bookingStart = Carbon::parse($booking->start_at);
                        $bookingEnd = Carbon::parse($booking->end_at);

                        if ($slotStart < $bookingEnd && $slotEnd > $bookingStart) {
                            return false;
                        }
                    }
                    return true;
                } else {
                    // Con empleados: contar cuántos están ocupados en este slot
                    $occupiedEmployees = 0;
                    foreach ($existingBookings as $booking) {
                        $bookingStart = Carbon::parse($booking->start_at);
                        $bookingEnd = Carbon::parse($booking->end_at);

                        if ($slotStart < $bookingEnd && $slotEnd > $bookingStart) {
                            $occupiedEmployees++;
                        }
                    }
                    
                    // El slot está disponible si hay al menos un empleado libre
                    return $occupiedEmployees < $employeeCount;
                }
            });
        } else {
            // Caso: No se seleccionó empleado pero el horario es específico de un empleado
            // (Este caso no debería ocurrir normalmente, pero por seguridad)
            $existingBookings = Booking::where('business_id', $businessId)
                ->whereDate('start_at', $date->format('Y-m-d'))
                ->where('status', 'confirmed')
                ->where('employee_id', $schedule->employee_id)
                ->get();

            $availableSlots = array_filter($slots, function($slot) use ($existingBookings, $date, $service) {
                $slotStart = Carbon::parse($date->format('Y-m-d') . ' ' . $slot);
                $slotEnd = $slotStart->copy()->addMinutes($service->duration_minutes);

                foreach ($existingBookings as $booking) {
                    $bookingStart = Carbon::parse($booking->start_at);
                    $bookingEnd = Carbon::parse($booking->end_at);

                    if ($slotStart < $bookingEnd && $slotEnd > $bookingStart) {
                        return false;
                    }
                }

                return true;
            });
        }

        return response()->json([
            'available_slots' => array_values($availableSlots),
            'schedule' => [
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'is_general' => $schedule->employee_id === null,
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
        'employee_id' => 'nullable|exists:employees,id',
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
    $business = Business::with('employees')->findOrFail($request->business_id);
    
    // Verificar que el servicio pertenece al negocio
    if ($service->business_id != $request->business_id) {
        return response()->json(['message' => 'Servicio no válido'], 400);
    }

    // CORRECCIÓN: Combinar fecha y hora correctamente
    $startAt = Carbon::parse($request->date . ' ' . $request->time);
    $endAt = $startAt->copy()->addMinutes($service->duration_minutes);
    $weekday = $startAt->dayOfWeek;

    $assignedEmployeeId = $request->employee_id;

    // Si NO se especificó empleado, verificar si hay horario general y asignar empleado disponible
    if (!$assignedEmployeeId) {
        // Verificar si el horario es general (sin empleado específico)
        $schedule = Schedule::where('business_id', $request->business_id)
            ->where('weekday', $weekday)
            ->whereNull('employee_id')
            ->first();

        if ($schedule) {
            $activeEmployees = $business->employees()->where('is_active', true)->pluck('id')->toArray();
            
            if (count($activeEmployees) > 0) {
                // Buscar un empleado disponible en este horario
                foreach ($activeEmployees as $empId) {
                    $hasConflict = Booking::where('business_id', $request->business_id)
                        ->where('employee_id', $empId)
                        ->where('status', 'confirmed')
                        ->where(function($query) use ($startAt, $endAt) {
                            $query->where(function($q) use ($startAt, $endAt) {
                                $q->where('start_at', '<', $endAt)
                                  ->where('end_at', '>', $startAt);
                            });
                        })
                        ->exists();
                    
                    if (!$hasConflict) {
                        $assignedEmployeeId = $empId;
                        break;
                    }
                }

                // Si todos los empleados están ocupados
                if (!$assignedEmployeeId) {
                    return response()->json([
                        'message' => 'Este horario ya no está disponible. Todos los empleados están ocupados.'
                    ], 409);
                }
            }
            // Si no hay empleados activos, se permite la reserva sin empleado
        }
    }

    // Log para debug
    Log::info('Creando reserva', [
        'date' => $request->date,
        'time' => $request->time,
        'employee_id' => $assignedEmployeeId,
        'start_at' => $startAt->toDateTimeString(),
        'end_at' => $endAt->toDateTimeString(),
    ]);

    // Verificar disponibilidad final (considerar empleado asignado)
    $conflictQuery = Booking::where('business_id', $request->business_id)
        ->where('status', 'confirmed')
        ->where(function($query) use ($startAt, $endAt) {
            $query->where(function($q) use ($startAt, $endAt) {
                $q->where('start_at', '<', $endAt)
                  ->where('end_at', '>', $startAt);
            });
        });
    
    if ($assignedEmployeeId) {
        $conflictQuery->where('employee_id', $assignedEmployeeId);
    }
    
    $conflict = $conflictQuery->exists();

    if ($conflict) {
        return response()->json([
            'message' => 'Este horario ya no está disponible'
        ], 409);
    }

    $booking = Booking::create([
        'business_id' => $request->business_id,
        'service_id' => $service->id,
        'employee_id' => $assignedEmployeeId,
        'user_id' => auth()->id(), // Puede ser null para clientes sin cuenta
        'customer_name' => $request->customer_name,
        'customer_email' => $request->customer_email,
        'start_at' => $startAt,
        'end_at' => $endAt,
        'status' => 'confirmed',
    ]);

    // Enviar correo de confirmación
        try {
            Log::info('Intentando enviar correo de confirmación', [
                'booking_id' => $booking->id,
                'email' => $booking->customer_email,
            ]);
            
            Mail::to($booking->customer_email)->send(new BookingConfirmationMail($booking));
            
            Log::info('Correo enviado exitosamente', [
                'booking_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error enviando correo de confirmación de reserva', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $booking->id,
                'customer_email' => $booking->customer_email,
            ]);
        }

            return response()->json([
                'message' => 'Reserva creada exitosamente',
                'booking' => $booking->load(['service', 'employee'])
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

        $bookings = Booking::with(['service', 'employee'])
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