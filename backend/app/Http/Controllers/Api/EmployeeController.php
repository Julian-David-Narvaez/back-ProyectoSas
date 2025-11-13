<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Obtener todos los empleados de un negocio
     */
    public function index($businessId)
    {
        $business = Business::findOrFail($businessId);
        
        // Verificar que el usuario sea dueño del negocio o superadmin
        if (auth()->user()->id !== $business->user_id && !auth()->user()->is_superadmin) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $employees = $business->employees()->with('schedules')->get();
        
        return response()->json($employees);
    }

    /**
     * Obtener empleados activos de un negocio (público)
     */
    public function publicIndex($businessId)
    {
        $employees = Employee::where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
        
        return response()->json($employees);
    }

    /**
     * Crear un nuevo empleado
     */
    public function store(Request $request, $businessId)
    {
        $business = Business::findOrFail($businessId);
        
        // Verificar que el usuario sea dueño del negocio
        if (auth()->user()->id !== $business->user_id && !auth()->user()->is_superadmin) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'order' => 'integer'
        ]);

        $validated['business_id'] = $businessId;

        $employee = Employee::create($validated);

        return response()->json($employee, 201);
    }

    /**
     * Obtener un empleado específico
     */
    public function show($businessId, $employeeId)
    {
        $business = Business::findOrFail($businessId);
        
        $employee = Employee::where('business_id', $businessId)
            ->where('id', $employeeId)
            ->with('schedules')
            ->firstOrFail();

        return response()->json($employee);
    }

    /**
     * Actualizar un empleado
     */
    public function update(Request $request, $businessId, $employeeId)
    {
        $business = Business::findOrFail($businessId);
        
        // Verificar que el usuario sea dueño del negocio
        if (auth()->user()->id !== $business->user_id && !auth()->user()->is_superadmin) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $employee = Employee::where('business_id', $businessId)
            ->where('id', $employeeId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'order' => 'integer'
        ]);

        $employee->update($validated);

        return response()->json($employee);
    }

    /**
     * Eliminar un empleado
     */
    public function destroy($businessId, $employeeId)
    {
        $business = Business::findOrFail($businessId);
        
        // Verificar que el usuario sea dueño del negocio
        if (auth()->user()->id !== $business->user_id && !auth()->user()->is_superadmin) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $employee = Employee::where('business_id', $businessId)
            ->where('id', $employeeId)
            ->firstOrFail();

        $employee->delete();

        return response()->json(['message' => 'Empleado eliminado correctamente']);
    }

    /**
     * Obtener disponibilidad de un empleado específico
     */
    public function getAvailability($businessId, $employeeId, Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'service_id' => 'required|exists:services,id'
        ]);

        $employee = Employee::where('business_id', $businessId)
            ->where('id', $employeeId)
            ->where('is_active', true)
            ->firstOrFail();

        $date = \Carbon\Carbon::parse($validated['date']);
        $service = \App\Models\Service::findOrFail($validated['service_id']);
        
        // Obtener horario del empleado para ese día de la semana
        $weekday = $date->dayOfWeek;
        $schedule = $employee->schedules()
            ->where('weekday', $weekday)
            ->first();

        if (!$schedule) {
            return response()->json(['slots' => []]);
        }

        // Obtener reservas existentes del empleado para ese día
        $existingBookings = \App\Models\Booking::where('employee_id', $employeeId)
            ->whereDate('start_at', $date->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->get();

        // Generar slots disponibles
        $slots = [];
        $startTime = \Carbon\Carbon::parse($schedule->start_time);
        $endTime = \Carbon\Carbon::parse($schedule->end_time);
        $duration = $service->duration_minutes;

        while ($startTime->copy()->addMinutes($duration) <= $endTime) {
            $slotStart = $date->copy()->setTimeFrom($startTime);
            $slotEnd = $slotStart->copy()->addMinutes($duration);

            // Verificar si el slot está ocupado
            $isAvailable = true;
            foreach ($existingBookings as $booking) {
                $bookingStart = \Carbon\Carbon::parse($booking->start_at);
                $bookingEnd = \Carbon\Carbon::parse($booking->end_at);

                if ($slotStart < $bookingEnd && $slotEnd > $bookingStart) {
                    $isAvailable = false;
                    break;
                }
            }

            if ($isAvailable) {
                $slots[] = [
                    'start' => $slotStart->format('Y-m-d H:i:s'),
                    'end' => $slotEnd->format('Y-m-d H:i:s'),
                ];
            }

            $startTime->addMinutes(30); // Intervalos de 30 minutos
        }

        return response()->json(['slots' => $slots]);
    }
}
