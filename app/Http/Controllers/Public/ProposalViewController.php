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
        $p = Proposal::with(['items', 'owner:id,name', 'lead'])->where('public_token', $token)->firstOrFail(); // los borradores se pueden ver (sin contar visitas ni responder)

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
        $p = Proposal::where('public_token', $token)->firstOrFail();

        return \App\Http\Controllers\Proposals\ProposalController::pdfResponse($p, $pdf);
    }

    /** El cliente responde desde el enlace: firma y acepta, solicita ajustes o rechaza. */
    public function answer(Request $request, string $token): RedirectResponse
    {
        $p = Proposal::where('public_token', $token)->whereIn('status', ['sent', 'viewed', 'changes_requested'])->firstOrFail();
        abort_if($p->effectiveStatus() === 'expired', 410, 'La propuesta venció.');

        $data = $request->validate([
            'action' => ['required', 'in:accept,reject,changes'],
            'name' => ['required', 'string', 'max:160'],
            'rut' => ['nullable', 'string', 'max:20', function ($a, $v, $fail) {
                if ($v && ! \App\Support\Rut::isValid($v)) {
                    $fail('El RUT no es válido (revisa el dígito verificador).');
                }
            }],
            'note' => ['nullable', 'string', 'max:2000', 'required_if:action,changes'],
            'signature' => ['nullable', 'string', 'max:400000', 'required_if:action,accept'],
        ], [
            'name.required' => 'Indica tu nombre para registrar la respuesta.',
            'note.required_if' => 'Cuéntanos qué ajustes necesitas.',
            'signature.required_if' => 'Dibuja tu firma para aceptar la propuesta.',
        ]);

        $signature = null;
        if ($data['action'] === 'accept') {
            $signature = $this->cleanSignature((string) $data['signature']);
            abort_if($signature === null, 422, 'La firma no es válida. Vuelve a dibujarla.');
        }

        $status = ['accept' => 'accepted', 'reject' => 'rejected', 'changes' => 'changes_requested'][$data['action']];

        $p->forceFill([
            'status' => $status,
            'responded_at' => now(),
            'responded_by' => $data['name'],
            'signer_rut' => filled($data['rut'] ?? null) ? \App\Support\Rut::format($data['rut']) : null,
            'response_note' => $data['note'] ?? null,
            'signature_data' => $signature,
            'response_ip' => $request->ip(),
            'response_user_agent' => mb_substr((string) $request->userAgent(), 0, 400),
        ])->save();

        $label = ['accepted' => '✅ Propuesta firmada y aceptada', 'rejected' => 'Propuesta rechazada', 'changes_requested' => '✏️ Ajustes solicitados en la propuesta'][$status];
        if ($p->lead) {
            app(LeadService::class)->log($p->lead, 'proposal', null, "{$label} por {$data['name']} ({$p->number})", ['proposal_id' => $p->id]);
        }

        try {
            app(\App\Services\Proposals\ProposalMailer::class)->notifyResponse($p->fresh(['owner:id,name,email']), $label);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('proposal_ok', match ($status) {
            'accepted' => '¡Gracias! Registramos tu firma y aceptación; te contactaremos a la brevedad.',
            'changes_requested' => 'Recibimos tus comentarios. Prepararemos una nueva versión y te la enviaremos.',
            default => 'Registramos tu respuesta. Gracias por revisar la propuesta.',
        });
    }

    /** Acepta solo un PNG razonable (data URL); evita guardar cualquier cosa en la firma. */
    private function cleanSignature(string $dataUrl): ?string
    {
        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
            return null;
        }
        $bin = base64_decode($m[1], true);
        if ($bin === false || strlen($bin) < 300 || strlen($bin) > 250000 || ! str_starts_with($bin, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        return $dataUrl;
    }
}
