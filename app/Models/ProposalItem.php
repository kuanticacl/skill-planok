<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['proposal_id', 'service_id', 'name', 'description', 'deliverables', 'billing', 'unit', 'quantity', 'unit_price', 'discount_pct', 'sort_order'])]
class ProposalItem extends Model
{
    protected function casts(): array
    {
        return ['deliverables' => 'array', 'quantity' => 'float', 'unit_price' => 'integer', 'discount_pct' => 'integer'];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /** Total neto de la línea (CLP). */
    public function lineTotal(): int
    {
        return (int) round($this->quantity * $this->unit_price * (1 - $this->discount_pct / 100));
    }
}
