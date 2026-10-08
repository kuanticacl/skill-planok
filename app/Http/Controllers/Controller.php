<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

abstract class Controller
{
    /** Muestra un toast (sonner) en la siguiente página. */
    protected function toast(string $message, string $type = 'success'): void
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);
    }
}
