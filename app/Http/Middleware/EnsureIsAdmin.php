<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class EnsureIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            Log::warning('Intento de acceso no autenticado a ruta de admin', [
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);
            abort(401, 'No autenticado');
        }

        if (!$user || !$user->isAdmin()) {
            abort($user ? 403 : 401, $user ? 'Acceso solo para administradores' : 'No autenticado');
        }

        return $next($request);
    }
}
