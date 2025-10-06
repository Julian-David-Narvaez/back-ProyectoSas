<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\WorkingHour;
use Illuminate\Http\Request;

class WorkingHourController extends Controller
{
    public function index($businessId)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $workingHours = $business->workingHours()
            ->orderBy('day_of_week')
            ->get();

        return response()->json($workingHours);
    }

    public function store(Request $request, $businessId)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'sometimes|boolean'
        ]);

        // Verificar que no exista ya un horario para ese día
        $exists = WorkingHour::where('business_id', $businessId)
            ->where('day_of_week', $request->day_of_week)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ya existe un horario para este día'
            ], 422);
        }

        $data = $request->only([
            'day_of_week',
            'start_time',
            'end_time',
            'is_active'
        ]);

        $data['business_id'] = $business->id;

        $workingHour = WorkingHour::create($data);

        return response()->json($workingHour, 201);
    }

    public function update(Request $request, $businessId, $id)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $workingHour = WorkingHour::where('business_id', $businessId)
            ->findOrFail($id);

        $request->validate([
            'day_of_week' => 'sometimes|required|integer|min:0|max:6',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'is_active' => 'sometimes|boolean'
        ]);

        $workingHour->update($request->only([
            'day_of_week',
            'start_time',
            'end_time',
            'is_active'
        ]));

        return response()->json($workingHour);
    }

    public function destroy($businessId, $id)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $workingHour = WorkingHour::where('business_id', $businessId)
            ->findOrFail($id);

        $workingHour->delete();

        return response()->json(['message' => 'Horario eliminado exitosamente']);
    }

    // Método para establecer horarios masivamente
    public function bulkStore(Request $request, $businessId)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|integer|min:0|max:6',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.is_active' => 'sometimes|boolean'
        ]);

        // Eliminar horarios existentes
        WorkingHour::where('business_id', $businessId)->delete();

        // Crear nuevos horarios
        $workingHours = [];
        foreach ($request->schedules as $schedule) {
            $schedule['business_id'] = $business->id;
            $workingHours[] = WorkingHour::create($schedule);
        }

        return response()->json($workingHours, 201);
    }
}