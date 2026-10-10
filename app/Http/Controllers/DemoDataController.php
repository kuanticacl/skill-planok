<?php

namespace App\Http\Controllers;

use App\Services\DemoPurge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DemoDataController extends Controller
{
    public function index(DemoPurge $purge): Response
    {
        return Inertia::render('crm/DemoData', [
            'demo' => $purge->preview('demo'),
            'all' => $purge->preview('all'),
        ]);
    }

    public function purge(Request $request, DemoPurge $purge): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(DemoPurge::MODES)],
            'confirm' => ['required', 'in:LIMPIAR'],
        ], ['confirm.in' => 'Escribe LIMPIAR para confirmar.']);

        $done = $purge->purge($data['mode']);

        $this->toast('Limpieza lista: '.collect($done)->filter()->map(fn ($n, $k) => "$n $k")->implode(', ').'.');

        return back();
    }
}
