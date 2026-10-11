<?php

namespace App\Services\Agent\Tools;

use App\Models\Invoice;
use App\Services\Billing\InvoiceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

class MarkInvoicePaid extends CrmTool
{
    protected ?string $permission = 'billing.mark_paid';

    public function name(): string
    {
        return 'mark_invoice_paid';
    }

    public function description(): string
    {
        return 'Marca una factura/cobro como PAGADA (detiene los recordatorios). Úsalo solo cuando el usuario confirme que el pago se recibió.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'invoice_id' => $schema->integer()->required(),
            'payment_method' => $schema->string()->description('Transferencia, Webpay, Flow…'),
            'payment_reference' => $schema->string()->description('N° de operación o comprobante.'),
        ];
    }

    protected function run(Request $r): array|string
    {
        $i = Invoice::find((int) $r['invoice_id']);
        if (! $i) {
            return 'ERROR: cobro no encontrado.';
        }
        app(InvoiceService::class)->markPaid($i, $r['payment_method'] ?: null, $r['payment_reference'] ?: null);
        $this->ctx->record($this->name(), "Marcó como pagada la factura ".($i->number ?: '#'.$i->id), url('/billing?status=paid'));

        return 'OK: factura '.($i->number ?: '#'.$i->id).' marcada como pagada.';
    }
}
