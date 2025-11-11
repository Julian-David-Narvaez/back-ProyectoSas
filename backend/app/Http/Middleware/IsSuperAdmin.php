<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'superadmin') {
            return response()->json(['message' => 'Acceso restringido: requiere superadmin'], 403);
        }

        return $next($request);
    }
}
