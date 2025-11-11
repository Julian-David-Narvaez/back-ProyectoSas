<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $businesses = Business::where('user_id', $request->user()->id)->get();
        return response()->json($businesses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $business = Business::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Crear página por defecto
        $page = $business->page()->create([
            'template' => 'default',
        ]);

        // Crear bloques por defecto
        $page->blocks()->createMany([
            [
                'type' => 'hero',
                'order' => 1,
                'content' => [
                    'title' => $business->name,
                    'subtitle' => 'Reserva tu cita en línea',
                    'image_url' => null,
                ]
            ],
            [
                'type' => 'services',
                'order' => 2,
                'content' => [
                    'title' => 'Nuestros Servicios',
                ]
            ],
            [
                'type' => 'about',
                'order' => 3,
                'content' => [
                    'title' => 'Sobre Nosotros',
                    'text' => $business->description ?? 'Escribe aquí sobre tu negocio',
                ]
            ],
            [
                'type' => 'contact',
                'order' => 4,
                'content' => [
                    'title' => 'Contacto',
                    'phone' => '',
                    'email' => '',
                    'address' => '',
                ]
            ],
        ]);

        return response()->json($business->load('page.blocks'), 201);
    }

    public function show($id)
    {
        $business = Business::with(['services', 'schedules', 'page.blocks'])
            ->findOrFail($id);
        
        // Verificar que pertenece al usuario autenticado
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($business);
    }

    public function showBySlug($slug)
    {
        $business = Business::with(['services' => function($query) {
            $query->orderBy('order');
        }, 'page.blocks' => function($query) {
            $query->orderBy('order');
        }])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($business);
    }

    public function destroy($id)
    {
        $business = Business::with('page.blocks')->findOrFail($id);

        // Verificar que pertenece al usuario autenticado
        if ($business->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        DB::beginTransaction();
        try {
            // Eliminar bookings relacionados
            Booking::where('business_id', $business->id)->delete();

            // Eliminar servicios y horarios
            $business->services()->delete();
            $business->schedules()->delete();

            // Eliminar página y bloques asociados
            if ($business->page) {
                $business->page->blocks()->delete();
                $business->page()->delete();
            }

            // Finalmente eliminar el negocio
            $business->delete();

            DB::commit();

            return response()->json(['message' => 'Negocio eliminado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al eliminar negocio', 'error' => $e->getMessage()], 500);
        }
    }
}