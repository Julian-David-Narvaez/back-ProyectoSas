<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index($businessId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $schedules = $business->schedules()->with('employee')->orderBy('weekday')->get();
        return response()->json($schedules);
    }

    public function store(Request $request, $businessId)
    {
        $business = Business::with('page')->findOrFail($businessId);
        
        $authUser = auth()->user();
        $isSuperAdmin = isset($authUser->role) && in_array(strtolower($authUser->role), ['superadmin', 'super']);

        if ($business->user_id !== $authUser->id && !$isSuperAdmin) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Verificar si la página está activa (no aplica para superadmins)
        if (!$isSuperAdmin && $business->page && !$business->page->is_active) {
            return response()->json(['message' => 'No puedes crear horarios porque esta página está deshabilitada'], 403);
        }

        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'weekday' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $data = $request->all();
        $data['business_id'] = $businessId;

        $schedule = Schedule::create($data);

        return response()->json($schedule->load('employee'), 201);
    }

    public function update(Request $request, $businessId, $scheduleId)
    {
        $business = Business::with('page')->findOrFail($businessId);
        
        $authUser = auth()->user();
        $isSuperAdmin = isset($authUser->role) && in_array(strtolower($authUser->role), ['superadmin', 'super']);

        if ($business->user_id !== $authUser->id && !$isSuperAdmin) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Verificar si la página está activa (no aplica para superadmins)
        if (!$isSuperAdmin && $business->page && !$business->page->is_active) {
            return response()->json(['message' => 'No puedes editar horarios porque esta página está deshabilitada'], 403);
        }

        $schedule = Schedule::where('business_id', $businessId)
            ->findOrFail($scheduleId);

        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'weekday' => 'sometimes|integer|min:0|max:6',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
        ]);

        $schedule->update($request->all());

        return response()->json($schedule->load('employee'));
    }

    public function destroy($businessId, $scheduleId)
    {
        $business = Business::with('page')->findOrFail($businessId);
        
        $authUser = auth()->user();
        $isSuperAdmin = isset($authUser->role) && in_array(strtolower($authUser->role), ['superadmin', 'super']);

        if ($business->user_id !== $authUser->id && !$isSuperAdmin) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Verificar si la página está activa (no aplica para superadmins)
        if (!$isSuperAdmin && $business->page && !$business->page->is_active) {
            return response()->json(['message' => 'No puedes eliminar horarios porque esta página está deshabilitada'], 403);
        }

        $schedule = Schedule::where('business_id', $businessId)
            ->findOrFail($scheduleId);

        $schedule->delete();

        return response()->json(['message' => 'Horario eliminado'], 200);
    }
}