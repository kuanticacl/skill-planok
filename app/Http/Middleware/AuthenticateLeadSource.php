<?php

namespace App\Http\Middleware;

use App\Models\LeadSource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica la API de ingreso: la API key identifica el origen del lead.
 * Se acepta en el header X-Api-Key o como Bearer token.
 */
class AuthenticateLeadSource
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Api-Key') ?: $request->bearerToken();

        if (! $key) {
            return response()->json(['message' => 'Falta la API key (header X-Api-Key).'], 401);
        }

        $source = LeadSource::where('api_key', $key)->first();

        if (! $source) {
            return response()->json(['message' => 'API key inválida.'], 401);
        }

        if (! $source->is_active) {
            return response()->json(['message' => 'El origen está desactivado.'], 403);
        }

        $request->attributes->set('lead_source', $source);

        return $next($request);
    }
}
