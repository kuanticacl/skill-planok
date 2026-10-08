<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Autentica llamadas a la API con una API key (Bearer o X-Api-Key) y valida el permiso requerido. */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $plain = $request->bearerToken() ?: $request->header('X-Api-Key');

        if (! $plain) {
            return response()->json(['message' => 'Falta la API key (Authorization: Bearer …).'], 401);
        }

        $key = ApiKey::findByPlain($plain);

        if (! $key || ! $key->is_active) {
            return response()->json(['message' => 'API key inválida o desactivada.'], 401);
        }

        if (! $key->can($ability)) {
            return response()->json(['message' => "Esta API key no tiene el permiso «{$ability}»."], 403);
        }

        // Registra el último uso sin escribir en cada llamada.
        if (! $key->last_used_at || $key->last_used_at->lt(now()->subMinute())) {
            $key->forceFill(['last_used_at' => now(), 'last_used_ip' => $request->ip()])->saveQuietly();
        }

        $request->attributes->set('api_key', $key);

        return $next($request);
    }
}
