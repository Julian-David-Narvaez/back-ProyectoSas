<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Client;
use App\Models\Service;
use App\Models\WorkingHour;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    // Obtener citas del negocio (admin)
    public function index(Request $request, $businessId)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $query = $business->appointments()
            ->with(['service', 'client']);

        // Filtros opcionales
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->where('appointment_date', $request->date);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('appointment_date', [
                $request->from_date,
                $request->to_date
            ]);
        }

        $appointments = $query->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        return response()->json($appointments);
    }

    // Crear cita (pública - clientes)
    public function book(Request $request)
    {
        $request->validate([
            'business_slug' => 'required|string',
            'service_id' => 'required|exists:services,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email',
            'client_phone' => 'required|string|max:20',
            'notes' => 'nullable|string'
        ]);

        // Obtener negocio
        $business = Business::where('slug', $request->business_slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Verificar servicio
        $service = Service::where('id', $request->service_id)
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        // Calcular hora de fin
        $startTime = Carbon::createFromFormat('H:i', $request->start_time);
        $endTime = $startTime->copy()->addMinutes($service->duration_minutes);

        // Verificar disponibilidad
        $isAvailable = $this->checkAvailability(
            $business->id,
            $request->appointment_date,
            $request->start_time,
            $endTime->format('H:i')
        );

        if (!$isAvailable) {
            return response()->json([
                'message' => 'El horario seleccionado no está disponible'
            ], 422);
        }

        // Crear o buscar cliente
        $client = Client::firstOrCreate(
            ['email' => $request->client_email],
            [
                'name' => $request->client_name,
                'phone' => $request->client_phone
            ]
        );

        // Crear cita
        $appointment = Appointment::create([
            'business_id' => $business->id,
            'service_id' => $service->id,
            'client_id' => $client->id,
            'appointment_date' => $request->appointment_date,
            'start_time' => $request->start_time,
            'end_time' => $endTime->format('H:i'),
            'status' => 'pending',
            'notes' => $request->notes
        ]);

        return response()->json([
            'message' => 'Cita reservada exitosamente',
            'appointment' => $appointment->load(['service', 'client'])
        ], 201);
    }

    // Actualizar estado de cita (admin)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed'
        ]);

        $appointment = Appointment::with('business')->findOrFail($id);

        if ($appointment->business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $appointment->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'appointment' => $appointment->load(['service', 'client'])
        ]);
    }

    // Obtener horarios disponibles (público)
    public function availableSlots(Request $request, $slug)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'service_id' => 'required|exists:services,id'
        ]);

        $business = Business::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $service = Service::where('id', $request->service_id)
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->firstOrFail();

        $date = Carbon::parse($request->date);
        $dayOfWeek = $date->dayOfWeek;

        // Obtener horario de trabajo para ese día
        $workingHour = WorkingHour::where('business_id', $business->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$workingHour) {
            return response()->json([
                'available_slots' => [],
                'message' => 'No hay horario disponible para este día'
            ]);
        }

        // Generar slots disponibles
        $slots = $this->generateTimeSlots(
            $workingHour->start_time,
            $workingHour->end_time,
            $service->duration_minutes,
            $business->id,
            $request->date
        );

        return response()->json([
            'available_slots' => $slots,
            'working_hours' => [
                'start' => $workingHour->start_time,
                'end' => $workingHour->end_time
            ]
        ]);
    }

    // Método privado para verificar disponibilidad
    private function checkAvailability($businessId, $date, $startTime, $endTime)
    {
        $conflict = Appointment::where('business_id', $businessId)
            ->where('appointment_date', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();

        return !$conflict;
    }

    // Método privado para generar slots de tiempo
    private function generateTimeSlots($startTime, $endTime, $duration, $businessId, $date)
    {
        $slots = [];
        $current = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        while ($current->addMinutes($duration)->lte($end)) {
            $slotStart = $current->copy()->subMinutes($duration)->format('H:i');
            $slotEnd = $current->format('H:i');

            $isAvailable = $this->checkAvailability(
                $businessId,
                $date,
                $slotStart,
                $slotEnd
            );

            if ($isAvailable) {
                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                    'available' => true
                ];
            }
        }

        return $slots;
    }

    // Obtener estadísticas (admin)
    public function statistics($businessId)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $today = now()->toDateString();
        $thisMonth = now()->format('Y-m');

        $stats = [
            'today' => [
                'total' => Appointment::where('business_id', $businessId)
                    ->where('appointment_date', $today)
                    ->count(),
                'pending' => Appointment::where('business_id', $businessId)
                    ->where('appointment_date', $today)
                    ->where('status', 'pending')
                    ->count(),
                'confirmed' => Appointment::where('business_id', $businessId)
                    ->where('appointment_date', $today)
                    ->where('status', 'confirmed')
                    ->count()
            ],
            'month' => [
                'total' => Appointment::where('business_id', $businessId)
                    ->whereYear('appointment_date', now()->year)
                    ->whereMonth('appointment_date', now()->month)
                    ->count(),
                'completed' => Appointment::where('business_id', $businessId)
                    ->whereYear('appointment_date', now()->year)
                    ->whereMonth('appointment_date', now()->month)
                    ->where('status', 'completed')
                    ->count(),
                'cancelled' => Appointment::where('business_id', $businessId)
                    ->whereYear('appointment_date', now()->year)
                    ->whereMonth('appointment_date', now()->month)
                    ->where('status', 'cancelled')
                    ->count()
            ],
            'upcoming' => Appointment::where('business_id', $businessId)
                ->upcoming()
                ->count()
        ];

        return response()->json($stats);
    }
}