<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Business;
use App\Models\Booking;
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
            'page_limit' => 'sometimes|integer|min:0',
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
        $request->validate([
            'page_limit' => 'required|integer|min:0',
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
}
