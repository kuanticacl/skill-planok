<?php

namespace App\Models;

use App\Services\UfService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cobro / factura de una empresa. El documento tributario lo emite un software externo: aquí se adjunta su PDF.
 * Estados: scheduled (cobro generado, aún sin PDF: el cliente no lo ve), issued (con PDF, por pagar), paid, cancelled.
 */
#[Fillable(['client_id', 'client_service_id', 'number', 'concept', 'period_start', 'period_end', 'issue_date', 'due_date', 'currency', 'amount_net', 'tax_rate', 'amount_total', 'uf_value', 'net_clp', 'total_clp', 'status', 'pdf_path', 'pdf_name', 'auto_remind', 'payment_link', 'sent_at', 'paid_at', 'payment_method', 'payment_reference', 'notes', 'created_by'])]
class Invoice extends Model
{
    use SoftDeletes;

    public const STATUSES = ['scheduled' => 'Por emitir', 'issued' => 'Por pagar', 'paid' => 'Pagada', 'cancelled' => 'Anulada'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date', 'period_end' => 'date', 'issue_date' => 'date', 'due_date' => 'date',
            'amount_net' => 'float', 'tax_rate' => 'float', 'amount_total' => 'float', 'uf_value' => 'float',
            'net_clp' => 'float', 'total_clp' => 'float', 'auto_remind' => 'boolean',
            'sent_at' => 'datetime', 'paid_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ClientService::class, 'client_service_id')->withTrashed();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(InvoiceReminder::class);
    }

    /** Facturas que el cliente puede ver en el portal (con PDF, por pagar o pagadas). */
    public function scopeVisibleToClient(Builder $q): Builder
    {
        return $q->whereIn('status', ['issued', 'paid']);
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->where('status', 'issued')->whereDate('due_date', '<', today());
    }

    public function isOverdue(): bool
    {
        return $this->status === 'issued' && $this->due_date->lt(today());
    }

    /** Estado para mostrar: «issued» vencida se muestra como «overdue». */
    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status;
    }

    public function hasPdf(): bool
    {
        return (bool) $this->pdf_path;
    }

    /** Calcula total (neto + IVA) y los equivalentes en pesos (con la UF de la fecha de emisión/vencimiento si es en UF). */
    public function recalculate(): void
    {
        $decimals = $this->currency === 'UF' ? 2 : 0;
        $this->amount_total = round($this->amount_net * (1 + ($this->tax_rate ?? 0) / 100), $decimals);

        if ($this->currency === 'UF') {
            $uf = app(UfService::class)->forDate($this->issue_date ?? $this->due_date);
            $this->uf_value = $uf;
            $this->net_clp = $uf ? round($this->amount_net * $uf) : null;
            $this->total_clp = $uf ? round($this->amount_total * $uf) : null;
        } else {
            $this->uf_value = null;
            $this->net_clp = round($this->amount_net);
            $this->total_clp = round($this->amount_total);
        }
    }
}
