<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadActivityController extends Controller
{
    /** Registra un seguimiento manual (llamada, correo, reunión…) en el historial. */
    public function store(Request $request, Lead $lead, LeadService $leads): RedirectResponse
    {
        $this->authorize('note', $lead);

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(LeadActivity::MANUAL))],
            'description' => ['required', 'string', 'max:1000'],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $leads->log($lead, $data['type'], $request->user(), $data['description'], [], $data['occurred_at'] ?? null);

        $this->toast('Seguimiento registrado.');

        return back();
    }
}
