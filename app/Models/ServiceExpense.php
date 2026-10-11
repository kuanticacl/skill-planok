<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Costo o gasto de un servicio contratado (solo uso interno). */
#[Fillable(['client_service_id', 'concept', 'currency', 'amount', 'amount_clp', 'incurred_on', 'notes', 'created_by'])]
class ServiceExpense extends Model
{
    protected function casts(): array
    {
        return ['incurred_on' => 'date', 'amount' => 'float', 'amount_clp' => 'float'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ClientService::class, 'client_service_id');
    }
}
