<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabecera estándar «Server-Timing» (visible en la pestaña Network → Timing del navegador) con el tiempo total
 * de la aplicación, el de la base de datos y la cantidad de consultas. Solo se emite para administradores o con
 * PERF_HEADERS=true, para diagnosticar lentitud sin exponer nada a otros usuarios.
 */
class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $queries = 0;
        $dbMs = 0.0;
        DB::listen(function ($q) use (&$queries, &$dbMs) {
            $queries++;
            $dbMs += $q->time;
        });

        $response = $next($request);

        if (config('app.perf_headers') || $request->user()?->role?->isAdmin()) {
            $total = (microtime(true) - $start) * 1000;
            $response->headers->set('Server-Timing', sprintf('app;dur=%.1f, db;dur=%.1f;desc="%d consultas", mem;desc="%.1f MB"', $total, $dbMs, $queries, memory_get_peak_usage(true) / 1048576));
        }

        return $response;
    }
}
