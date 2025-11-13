<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Business;
use App\Models\Booking;
use App\Models\Page;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    // Listar usuarios
    public function index()
    {
        $users = User::select('id','name','email','role','page_limit','created_at')->get();
        return response()->json($users);
    }

    // Crear usuario (superadmin puede crear cuentas y asignar page_limit)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'sometimes|string',
            // Allow -1 to represent "unlimited"
            'page_limit' => 'sometimes|integer|min:-1',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'client',
            'page_limit' => $request->page_limit ?? 1,
        ]);

        return response()->json($user, 201);
    }

    // Actualizar page_limit de un usuario
    public function updatePageLimit(Request $request, $id)
    {
        // Allow -1 to represent "unlimited"
        $request->validate([
            'page_limit' => 'required|integer|min:-1',
        ]);

        $user = User::findOrFail($id);
        $user->page_limit = $request->page_limit;
        $user->save();

        return response()->json(['message' => 'page_limit actualizado', 'user' => $user]);
    }

    // Eliminar usuario y sus recursos
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        DB::beginTransaction();
        try {
            // Eliminar todos los negocios del usuario y sus relaciones
            $businesses = Business::where('user_id', $user->id)->get();
            foreach ($businesses as $business) {
                Booking::where('business_id', $business->id)->delete();
                $business->services()->delete();
                $business->schedules()->delete();
                if ($business->page) {
                    $business->page->blocks()->delete();
                    $business->page()->delete();
                }
                $business->delete();
            }

            $user->tokens()->delete();
            $user->delete();

            DB::commit();
            return response()->json(['message' => 'Usuario eliminado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al eliminar usuario', 'error' => $e->getMessage()], 500);
        }
    }

    // Listar todas las páginas con información del usuario y negocio
    public function listPages()
    {
        $pages = Page::with(['business.user:id,name,email'])
            ->select('id', 'business_id', 'template', 'is_active', 'created_at')
            ->get()
            ->map(function ($page) {
                return [
                    'id' => $page->id,
                    'business_id' => $page->business_id,
                    'business_name' => $page->business->name ?? 'N/A',
                    'business_slug' => $page->business->slug ?? 'N/A',
                    'user_id' => $page->business->user->id ?? null,
                    'user_name' => $page->business->user->name ?? 'N/A',
                    'user_email' => $page->business->user->email ?? 'N/A',
                    'template' => $page->template,
                    'is_active' => $page->is_active,
                    'created_at' => $page->created_at,
                ];
            });

        return response()->json($pages);
    }

    // Cambiar el estado de una o varias páginas
    public function togglePages(Request $request)
    {
        $request->validate([
            'page_ids' => 'required|array',
            'page_ids.*' => 'exists:pages,id',
            'is_active' => 'required|boolean',
        ]);

        Page::whereIn('id', $request->page_ids)->update(['is_active' => $request->is_active]);

        return response()->json([
            'message' => 'Estado de páginas actualizado correctamente',
            'count' => count($request->page_ids)
        ]);
    }

    // Deshabilitar todas las páginas de un usuario
    public function disableUserPages(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        
        $businessIds = Business::where('user_id', $userId)->pluck('id');
        $count = Page::whereIn('business_id', $businessIds)->update(['is_active' => false]);

        return response()->json([
            'message' => 'Todas las páginas del usuario han sido deshabilitadas',
            'count' => $count
        ]);
    }

    // Habilitar todas las páginas de un usuario
    public function enableUserPages(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        
        $businessIds = Business::where('user_id', $userId)->pluck('id');
        $count = Page::whereIn('business_id', $businessIds)->update(['is_active' => true]);

        return response()->json([
            'message' => 'Todas las páginas del usuario han sido habilitadas',
            'count' => $count
        ]);
    }
}