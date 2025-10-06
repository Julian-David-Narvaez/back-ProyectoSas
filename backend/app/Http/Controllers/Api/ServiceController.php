<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index($businessId)
    {
        $business = Business::findOrFail($businessId);
        
        // Verificar autorización
        if (auth()->check() && $business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $services = $business->services()->get();

        return response()->json($services);
    }

    public function store(Request $request, $businessId)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'sometimes|boolean'
        ]);

        $data = $request->only([
            'name',
            'description',
            'duration_minutes',
            'price',
            'is_active'
        ]);

        $data['business_id'] = $business->id;

        // Manejar imagen
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('services', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $service = Service::create($data);

        return response()->json($service, 201);
    }

    public function show($businessId, $id)
    {
        $business = Business::findOrFail($businessId);
        
        if (auth()->check() && $business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $service = Service::where('business_id', $businessId)
            ->findOrFail($id);

        return response()->json($service);
    }

    public function update(Request $request, $businessId, $id)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $service = Service::where('business_id', $businessId)
            ->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'sometimes|required|integer|min:5|max:480',
            'price' => 'sometimes|required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'sometimes|boolean'
        ]);

        $data = $request->only([
            'name',
            'description',
            'duration_minutes',
            'price',
            'is_active'
        ]);

        // Manejar imagen
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior
            if ($service->image_url) {
                $oldPath = str_replace('/storage/', '', parse_url($service->image_url, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('services', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $service->update($data);

        return response()->json($service);
    }

    public function destroy($businessId, $id)
    {
        $business = Business::findOrFail($businessId);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $service = Service::where('business_id', $businessId)
            ->findOrFail($id);

        // Eliminar imagen
        if ($service->image_url) {
            $path = str_replace('/storage/', '', parse_url($service->image_url, PHP_URL_PATH));
            Storage::disk('public')->delete($path);
        }

        $service->delete();

        return response()->json(['message' => 'Servicio eliminado exitosamente']);
    }

    // Método público para obtener servicios de un negocio
    public function publicIndex($slug)
    {
        $business = Business::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $services = $business->services()
            ->where('is_active', true)
            ->get();

        return response()->json($services);
    }
}