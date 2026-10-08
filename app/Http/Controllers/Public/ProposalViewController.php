<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Services\LeadService;
use App\Services\Proposals\ProposalPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Enlace público de la propuesta: la ve el cliente (sin iniciar sesión), puede descargarla y aceptarla. */
class ProposalViewController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $p = Proposal::with(['items', 'owner:id,name', 'lead'])->where('public_token', $token)->where('status', '!=', 'draft')->firstOrFail();

        // Las visitas del equipo (con sesión) no cuentan como «vista del cliente».
        if (! $request->user() && in_array($p->status, ['sent', 'viewed'], true)) {
            $first = $p->viewed_at === null;
            $p->forceFill(['status' => 'viewed', 'viewed_at' => $p->viewed_at ?? now(), 'view_count' => $p->view_count + 1])->save();
            if ($first && $p->lead) {
                app(LeadService::class)->log($p->lead, 'proposal', null, "El cliente abrió la propuesta {$p->number}", ['proposal_id' => $p->id]);
            }
        }

        return response(view('proposals.document', ['p' => $p, 'public' => true, 'preview' => false])->render())
            ->header('Cache-Control', 'no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function pdf(string $token, ProposalPdf $pdf): Response
    {
        $p = Proposal::where('public_token', $token)->where('status', '!=', 'draft')->firstOrFail();

        return \App\Http\Controllers\Proposals\ProposalController::pdfResponse($p, $pdf);
    }

    public function answer(Request $request, string $token): RedirectResponse
    {
        $p = Proposal::where('public_token', $token)->whereIn('status', ['sent', 'viewed'])->firstOrFail();
        abort_if($p->effectiveStatus() === 'expired', 410, 'La propuesta venció.');

        $data = $request->validate([
            'action' => ['required', 'in:accept,reject'],
            'name' => ['required', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], ['name.required' => 'Indica tu nombre para registrar la respuesta.']);

        $accepted = $data['action'] === 'accept';
        $p->forceFill([
            'status' => $accepted ? 'accepted' : 'rejected',
            'responded_at' => now(),
            'responded_by' => $data['name'],
            'response_note' => $data['note'] ?? null,
            'response_ip' => $request->ip(),
        ])->save();

        if ($p->lead) {
            app(LeadService::class)->log($p->lead, 'proposal', null, ($accepted ? '✅ Propuesta aceptada' : 'Propuesta rechazada')." por {$data['name']} ({$p->number})", ['proposal_id' => $p->id]);
        }

        return back()->with('proposal_ok', $accepted ? '¡Gracias! Registramos tu aceptación y te contactaremos a la brevedad.' : 'Registramos tu respuesta. Gracias por revisar la propuesta.');
    }
}
