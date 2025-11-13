<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index($businessId)
    {
        $business = Business::findOrFail($businessId);
        
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $services = $business->services()->orderBy('order')->get();
        return response()->json($services);
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
            return response()->json(['message' => 'No puedes crear servicios porque esta página está deshabilitada'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|string',
        ]);

        $service = $business->services()->create([
            'name' => $request->name,
            'duration_minutes' => $request->duration_minutes,
            'price' => $request->price,
            'image_url' => $request->image_url,
            'order' => $business->services()->max('order') + 1,
        ]);

        return response()->json($service, 201);
    }

    public function update(Request $request, $businessId, $serviceId)
    {
        $business = Business::with('page')->findOrFail($businessId);
        
        $authUser = auth()->user();
        $isSuperAdmin = isset($authUser->role) && in_array(strtolower($authUser->role), ['superadmin', 'super']);

        if ($business->user_id !== $authUser->id && !$isSuperAdmin) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Verificar si la página está activa (no aplica para superadmins)
        if (!$isSuperAdmin && $business->page && !$business->page->is_active) {
            return response()->json(['message' => 'No puedes editar servicios porque esta página está deshabilitada'], 403);
        }

        $service = Service::where('business_id', $businessId)
            ->findOrFail($serviceId);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'duration_minutes' => 'sometimes|integer|min:1',
            'price' => 'sometimes|numeric|min:0',
            'image_url' => 'nullable|string',
            'order' => 'sometimes|integer|min:0',
        ]);

        $service->update($request->all());

        return response()->json($service);
    }

    public function destroy($businessId, $serviceId)
    {
        $business = Business::with('page')->findOrFail($businessId);
        
        $authUser = auth()->user();
        $isSuperAdmin = isset($authUser->role) && in_array(strtolower($authUser->role), ['superadmin', 'super']);

        if ($business->user_id !== $authUser->id && !$isSuperAdmin) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Verificar si la página está activa (no aplica para superadmins)
        if (!$isSuperAdmin && $business->page && !$business->page->is_active) {
            return response()->json(['message' => 'No puedes eliminar servicios porque esta página está deshabilitada'], 403);
        }

        $service = Service::where('business_id', $businessId)
            ->findOrFail($serviceId);

        $service->delete();

        return response()->json(['message' => 'Servicio eliminado'], 200);
    }
}