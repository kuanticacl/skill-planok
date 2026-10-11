<?php

namespace App\Services\Agent\Tools;

use App\Models\ClientService;
use App\Services\Billing\InvoiceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Ai\Tools\Request;

class CreateInvoice extends CrmTool
{
    protected ?string $permission = 'billing.manage';

    public function name(): string
    {
        return 'create_invoice';
    }

    public function description(): string
    {
        return 'Crea un COBRO (queda «por emitir»). El PDF de la factura lo adjunta una persona desde Facturación (tú no puedes subir archivos): avísalo y entrega el enlace. Monto NETO; el IVA se agrega solo.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'client_id' => $schema->integer()->required(),
            'concept' => $schema->string()->required(),
            'amount_net' => $schema->number()->required(),
            'currency' => $schema->string()->enum(['CLP', 'UF']),
            'due_date' => $schema->string()->required()->description('YYYY-MM-DD'),
            'client_service_id' => $schema->integer()->description('Servicio contratado asociado (opcional).'),
            'number' => $schema->string()->description('N° de factura si ya se conoce.'),
            'auto_remind' => $schema->boolean()->description('Recordatorios automáticos de pago (para cobros recurrentes).'),
            'payment_link' => $schema->string(),
        ];
    }

    protected function run(Request $r): array|string
    {
        $d = $r->validate([
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'concept' => ['required', 'string', 'max:255'], 'amount_net' => ['required', 'numeric', 'min:0'], 'currency' => ['nullable', Rule::in(['CLP', 'UF'])],
            'due_date' => ['required', 'date'], 'client_service_id' => ['nullable', 'integer', Rule::exists('client_services', 'id')->whereNull('deleted_at')],
            'number' => ['nullable', 'string', 'max:60'], 'auto_remind' => ['nullable', 'boolean'], 'payment_link' => ['nullable', 'url', 'max:500'],
        ]);
        if (! empty($d['client_service_id']) && ClientService::find($d['client_service_id'])?->client_id !== (int) $d['client_id']) {
            return 'ERROR: el servicio pertenece a otra empresa.';
        }

        $i = app(InvoiceService::class)->create([...$d, 'currency' => $d['currency'] ?? 'CLP', 'auto_remind' => (bool) ($d['auto_remind'] ?? false)], $this->ctx->user);
        $this->ctx->record($this->name(), "Creó el cobro «{$i->concept}»", url('/billing?status=scheduled'));

        return ['id' => $i->id, 'estado' => 'Por emitir (falta adjuntar el PDF en Facturación)', 'total_con_iva' => $i->amount_total, 'url' => url('/billing?status=scheduled')];
    }
}
