<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

abstract class Controller
{
    use AuthorizesRequests;

    /** Muestra un toast (sonner) en la siguiente página. */
    protected function toast(string $message, string $type = 'success'): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }

    /** JSON para llamadas fetch (Kanban, paneles); redirección + toast para navegación Inertia normal. */
    protected function respond(Request $request, array $json = [], ?string $message = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['ok' => true, ...$json]);
        }

        if ($message) {
            $this->toast($message);
        }

        return back();
    }
}
