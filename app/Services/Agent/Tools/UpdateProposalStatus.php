<?php

namespace App\Services\Agent\Tools;

use App\Models\Proposal;
use App\Services\LeadService;
use App\Services\Proposals\ProposalBuilder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

class UpdateProposalStatus extends CrmTool
{
    protected ?string $permission = 'proposals.send';

    public function name(): string
    {
        return 'update_proposal_status';
    }

    public function description(): string
    {
        return 'Cambia el estado de una propuesta manualmente: «sent» (marcar como enviada/compartida; NO envía correo), «accepted» o «rejected» (registrar la respuesta del cliente recibida por otra vía) o «draft» (reabrir). '
            .'Pide confirmación al usuario antes de usar accepted/rejected.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'proposal_id' => $schema->integer()->required(),
            'status' => $schema->string()->enum(['draft', 'sent', 'accepted', 'rejected'])->required(),
            'note' => $schema->string()->description('Comentario del cliente o motivo (opcional).'),
        ];
    }

    protected function run(Request $r): array|string
    {
        $user = $this->ctx->user;
        $p = Proposal::visibleTo($user)->find((int) $r['proposal_id']);
        if (! $p) {
            return 'ERROR: propuesta no encontrada o sin acceso.';
        }
        $status = (string) $r['status'];
        if (! in_array($status, ['draft', 'sent', 'accepted', 'rejected'], true)) {
            return 'ERROR: estado inválido.';
        }
        if ($status === 'sent' && $p->items()->count() === 0) {
            return 'ERROR: la propuesta no tiene servicios.';
        }

        $wasDraft = $p->status === 'draft';
        $p->status = $status;
        if ($status === 'sent') {
            $p->sent_at ??= now();
            $p->issued_at ??= now();
        }
        if (in_array($status, ['draft', 'sent'], true)) {
            $p->forceFill(['responded_at' => null, 'responded_by' => null, 'signer_rut' => null, 'response_note' => null, 'signature_data' => null, 'response_ip' => null, 'response_user_agent' => null]);
        }
        if (in_array($status, ['accepted', 'rejected'], true)) {
            $p->forceFill(['responded_at' => now(), 'responded_by' => $user->name.' (registrado internamente)', 'response_note' => $r['note'] ?? null, 'signature_data' => null, 'signer_rut' => null]);
        }
        $p->save();
        if ($wasDraft && $status !== 'draft') {
            app(ProposalBuilder::class)->freezeUf($p);
        }
        if ($p->lead) {
            app(LeadService::class)->log($p->lead, 'proposal', $user, "Propuesta {$p->number}: ".mb_strtolower(Proposal::STATUSES[$status]).' (vía Agent)', ['proposal_id' => $p->id]);
        }
        $this->ctx->record($this->name(), "Propuesta {$p->number} → ".Proposal::STATUSES[$status], url('/proposals/'.$p->id));

        return "OK: la propuesta {$p->number} ahora está «".Proposal::STATUSES[$status].'».';
    }
}
