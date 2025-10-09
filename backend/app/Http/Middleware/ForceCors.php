<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceCors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Intenta tomar el origen permitido de la variable de entorno FRONTEND_URL
        $frontendEnv = env('FRONTEND_URL', '*');

        // Si FRONTEND_URL está como '*' o vacío, usamos el Origin de la petición
        $origin = $frontendEnv === '*' || empty($frontendEnv) ? $request->headers->get('Origin') : $frontendEnv;

        $headers = [
            'Access-Control-Allow-Origin' => $origin ?? '*',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,PATCH,DELETE,OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept',
            'Access-Control-Max-Age' => '3600',
        ];

        // Sólo añadir credenciales si el origen es explícito (no '*')
        $supportsCredentials = config('cors.supports_credentials', false);
        if ($supportsCredentials && $headers['Access-Control-Allow-Origin'] !== '*') {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        // Responder inmediatamente a preflight
        if ($request->getMethod() === 'OPTIONS') {
            return response()->noContent(204, $headers);
        }

        $response = $next($request);

        // Añadir cabeceras si no existen (no sobreescribimos las que ya estén presentes)
        foreach ($headers as $key => $value) {
            if (!$response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
