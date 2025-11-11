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
        $authUser = $request->user();

        // Determinar si el requester es superadmin
        $isSuperAdmin = isset($authUser->role) && in_array(strtolower($authUser->role), ['superadmin', 'super']);

        // Si es superadmin y provee user_id, listar los negocios de ese usuario
        $targetUserId = $request->query('user_id') && $isSuperAdmin ? $request->query('user_id') : $authUser->id;

        $businesses = Business::where('user_id', $targetUserId)->get();

        // Calcular permiso para crear desde la perspectiva del requester
        $canCreate = false;
        if ($isSuperAdmin) {
            $canCreate = true; // superadmin puede crear/gestionar para otros
        } else {
            $currentCount = Business::where('user_id', $authUser->id)->count();
            $limit = $authUser->page_limit ?? 1;
            // Si limit < 0 significa ilimitado
            if ($limit < 0) {
                $canCreate = true;
            } else {
                $canCreate = $currentCount < $limit;
            }
        }

        return response()->json([
            'data' => $businesses,
            'permissions' => [
                'can_create' => $canCreate,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $authUser = $request->user();
        $isSuperAdmin = isset($authUser->role) && in_array(strtolower($authUser->role), ['superadmin', 'super']);

        // Si el request incluye user_id y quien hace el request es superadmin, crear en nombre de ese usuario
        $ownerId = $authUser->id;
        if ($request->has('user_id') && $isSuperAdmin) {
            $ownerId = $request->input('user_id');
        }

        // Para usuarios normales verificar el page_limit
        if (! $isSuperAdmin) {
            $currentCount = Business::where('user_id', $authUser->id)->count();
            $limit = $authUser->page_limit ?? 1;
            // Si el límite es negativo considerarlo ilimitado
            if ($limit >= 0 && $currentCount >= $limit) {
                return response()->json(['message' => 'Has alcanzado el límite de páginas/negocios permitidas'], 403);
            }
        }

        $business = Business::create([
            'user_id' => $ownerId,
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
        
        // Verificar que pertenece al usuario autenticado, salvo si es superadmin
        $authUser = auth()->user();
        $isSuperAdmin = isset($authUser->role) && in_array(strtolower($authUser->role), ['superadmin', 'super']);

        // Si el propietario tiene page_limit == 0 y es el que intenta acceder, bloquear el acceso administrativo
        if ($business->user_id === $authUser->id && ! $isSuperAdmin && ($authUser->page_limit ?? 1) === 0) {
            return response()->json(['message' => 'Acceso bloqueado: tu cuenta no tiene permiso para gestionar páginas/negocios'], 403);
        }

        if ($business->user_id !== $authUser->id && ! $isSuperAdmin) {
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

        // Verificar que pertenece al usuario autenticado, salvo si es superadmin
        $authUser = auth()->user();
        $isSuperAdmin = isset($authUser->role) && in_array(strtolower($authUser->role), ['superadmin', 'super']);

        // Bloquear eliminación si el propietario tiene page_limit == 0 (no permitir gestión)
        if ($business->user_id === $authUser->id && ! $isSuperAdmin && ($authUser->page_limit ?? 1) === 0) {
            return response()->json(['message' => 'Acceso bloqueado: tu cuenta no tiene permiso para gestionar páginas/negocios'], 403);
        }

        if ($business->user_id !== $authUser->id && ! $isSuperAdmin) {
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