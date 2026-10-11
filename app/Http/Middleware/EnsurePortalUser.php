<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Solo accesos del portal de clientes (usuarios con empresa, activos). El equipo interno usa el CRM. */
class EnsurePortalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Vista previa: el equipo con permiso ve el portal tal como lo ve el cliente (solo lectura).
        if ($user && ! $user->isPortal() && $request->session()->has('portal_preview') && $user->hasPermission('portal.manage')) {
            return $next($request);
        }

        if (! $user || ! $user->isPortal() || ! $user->is_active) {
            return $user && ! $user->isPortal() ? redirect('/dashboard') : abort(403);
        }

        return $next($request);
    }
}
