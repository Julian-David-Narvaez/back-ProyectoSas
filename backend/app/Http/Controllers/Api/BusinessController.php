<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $businesses = $request->user()
            ->businesses()
            ->with(['services', 'workingHours'])
            ->latest()
            ->get();

        return response()->json($businesses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'logo' => 'nullable|image|max:2048'
        ]);

        $data = $request->only([
            'business_name',
            'description',
            'address',
            'phone',
            'email'
        ]);

        $data['user_id'] = $request->user()->id;

        // Manejar logo (por ahora sin Firebase)
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_url'] = Storage::url($path);
        }

        $business = Business::create($data);

        // Crear página por defecto
        $business->page()->create([
            'content' => [
                'sections' => []
            ],
            'theme_config' => [
                'primaryColor' => '#3B82F6',
                'secondaryColor' => '#10B981'
            ],
            'is_published' => false
        ]);

        return response()->json($business->load('page'), 201);
    }

    public function show($id)
    {
        $business = Business::with(['services', 'workingHours', 'page'])
            ->findOrFail($id);

        // Verificar que el usuario sea dueño del negocio
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($business);
    }

    public function update(Request $request, $id)
    {
        $business = Business::findOrFail($id);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'business_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'sometimes|boolean'
        ]);

        $data = $request->only([
            'business_name',
            'description',
            'address',
            'phone',
            'email',
            'is_active'
        ]);

        if ($request->hasFile('logo')) {
            // Eliminar logo anterior
            if ($business->logo_url) {
                $oldPath = str_replace('/storage/', '', parse_url($business->logo_url, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_url'] = Storage::url($path);
        }

        $business->update($data);

        return response()->json($business);
    }

    public function destroy($id)
    {
        $business = Business::findOrFail($id);

        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Eliminar logo
        if ($business->logo_url) {
            $path = str_replace('/storage/', '', parse_url($business->logo_url, PHP_URL_PATH));
            Storage::disk('public')->delete($path);
        }

        $business->delete();

        return response()->json(['message' => 'Negocio eliminado exitosamente']);
    }

    // Método público para obtener negocio por slug
    public function getBySlug($slug)
    {
        $business = Business::where('slug', $slug)
            ->where('is_active', true)
            ->with(['services' => function($query) {
                $query->where('is_active', true);
            }, 'workingHours' => function($query) {
                $query->where('is_active', true)->orderBy('day_of_week');
            }, 'page'])
            ->firstOrFail();

        return response()->json($business);
    }
}