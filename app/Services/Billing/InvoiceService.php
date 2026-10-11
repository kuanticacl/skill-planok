<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Reglas de negocio de cobros y facturas: crear, adjuntar el PDF, emitir, marcar pagada y anular. */
class InvoiceService
{
    public function __construct(private BillingMailer $mailer) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data, ?User $actor = null): Invoice
    {
        $invoice = new Invoice([
            ...$data,
            'status' => 'scheduled',
            'tax_rate' => $data['tax_rate'] ?? config('portal.tax_rate'),
            'created_by' => $actor?->id,
        ]);
        $invoice->recalculate();
        $invoice->save();

        return $invoice;
    }

    /** @param  array<string, mixed>  $data */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->fill($data);
        $invoice->recalculate();
        $invoice->save();

        return $invoice;
    }

    /** Guarda el PDF (disco privado). Reemplaza el anterior. */
    public function attachPdf(Invoice $invoice, UploadedFile $file): Invoice
    {
        $old = $invoice->pdf_path;
        $path = $file->storeAs("invoices/{$invoice->client_id}", $invoice->id.'-'.bin2hex(random_bytes(6)).'.pdf', 'local');
        $invoice->forceFill(['pdf_path' => $path, 'pdf_name' => mb_substr($file->getClientOriginalName(), 0, 200)])->save();

        if ($old && $old !== $path) {
            Storage::disk('local')->delete($old);
        }

        return $invoice;
    }

    /**
     * Emite el cobro: con PDF adjunto pasa a «por pagar» y queda visible para el cliente.
     * Los cobros de servicios recurrentes avisan solos; un cobro único se envía con el botón «Enviar cobro».
     */
    public function issue(Invoice $invoice, ?string $number = null): Invoice
    {
        if (! $invoice->hasPdf()) {
            throw ValidationException::withMessages(['pdf' => 'Adjunta primero el PDF de la factura.']);
        }
        if (! in_array($invoice->status, ['scheduled', 'issued'], true)) {
            throw ValidationException::withMessages(['status' => 'Este cobro ya no se puede emitir.']);
        }

        $invoice->fill(['number' => $number ?: $invoice->number, 'status' => 'issued', 'issue_date' => $invoice->issue_date ?? today()]);
        $invoice->recalculate();
        $invoice->save();

        return $invoice;
    }

    public function markPaid(Invoice $invoice, ?string $method = null, ?string $reference = null, ?\DateTimeInterface $paidAt = null): Invoice
    {
        if (! in_array($invoice->status, ['issued', 'scheduled'], true)) {
            throw ValidationException::withMessages(['status' => 'Solo se pueden marcar como pagadas facturas pendientes.']);
        }

        $invoice->forceFill(['status' => 'paid', 'paid_at' => $paidAt ?? now(), 'payment_method' => $method, 'payment_reference' => $reference])->save();

        return $invoice;
    }

    public function reopen(Invoice $invoice): Invoice
    {
        $invoice->forceFill(['status' => $invoice->hasPdf() ? 'issued' : 'scheduled', 'paid_at' => null, 'payment_method' => null, 'payment_reference' => null])->save();

        return $invoice;
    }

    public function cancel(Invoice $invoice): Invoice
    {
        $invoice->forceFill(['status' => 'cancelled'])->save();

        return $invoice;
    }

    /** Envío manual del cobro o un recordatorio. Solo facturas por pagar (con PDF). */
    public function send(Invoice $invoice, string $kind = 'manual'): int
    {
        if ($invoice->status !== 'issued') {
            throw ValidationException::withMessages(['status' => 'Primero emite la factura (adjunta el PDF); solo se envían cobros por pagar.']);
        }

        $count = $this->mailer->sendInvoice($invoice, $kind === 'issued' ? 'issued' : 'manual');
        if ($count === 0) {
            throw ValidationException::withMessages(['email' => 'La empresa no tiene contactos con acceso ni correo para enviar el cobro.']);
        }

        return $count;
    }
}
