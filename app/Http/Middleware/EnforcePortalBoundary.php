<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Separa el portal de clientes del CRM interno, aunque vivan en la misma aplicación:
 *  - Un acceso de portal (usuario con empresa) solo puede usar /portal, cerrar sesión, los enlaces públicos de propuesta y el health check.
 *  - En el dominio del portal (clientes.…) solo se atiende el portal y el inicio de sesión; el equipo es enviado al CRM.
 */
class EnforcePortalBoundary
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $portalHost = config('portal.domain');
        $onPortalHost = $portalHost && strcasecmp($request->getHost(), $portalHost) === 0;

        if ($user?->isPortal()) {
            if (! $this->allowedForPortal($request)) {
                return redirect('/portal');
            }

            return $next($request);
        }

        if ($onPortalHost && $request->isMethod('GET') && ! $this->isAuthPath($request) && ! $request->is('portal*', 'p/*', 'up')) {
            // Equipo interno entrando por el dominio del portal: se le lleva al CRM; visitantes, al inicio de sesión.
            return $user
                ? redirect()->away(rtrim(config('app.url'), '/').$request->getRequestUri())
                : redirect('/login');
        }

        return $next($request);
    }

    private function allowedForPortal(Request $request): bool
    {
        return $request->is('portal', 'portal/*', 'logout', 'p/*', 'up', 'build/*', 'brand/*', 'storage/*', 'livewire/*')
            || $request->is('two-factor-challenge', 'email/verify*', 'user/confirm-password*');
    }

    private function isAuthPath(Request $request): bool
    {
        return $request->is('login', 'forgot-password', 'reset-password/*', 'logout', 'two-factor-challenge', 'email/verify*');
    }
}
