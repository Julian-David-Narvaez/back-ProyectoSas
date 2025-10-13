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

        $schedules = $business->schedules()->orderBy('weekday')->get();
        return response()->json($schedules);
    }

    public function store(Request $request, $businessId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'weekday' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $schedule = $business->schedules()->create($request->all());

        return response()->json($schedule, 201);
    }

    public function update(Request $request, $businessId, $scheduleId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $schedule = Schedule::where('business_id', $businessId)
            ->findOrFail($scheduleId);

        $request->validate([
            'weekday' => 'sometimes|integer|min:0|max:6',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
        ]);

        $schedule->update($request->all());

        return response()->json($schedule);
    }

    public function destroy($businessId, $scheduleId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $schedule = Schedule::where('business_id', $businessId)
            ->findOrFail($scheduleId);

        $schedule->delete();

        return response()->json(['message' => 'Horario eliminado'], 200);
    }
}