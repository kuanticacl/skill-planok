<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lead_id', 'user_id', 'type', 'description', 'properties', 'occurred_at'])]
class LeadActivity extends Model
{
    /** Tipos que registra el sistema automáticamente. */
    public const AUTOMATIC = ['created', 'stage_changed', 'assigned', 'updated'];

    /** Seguimientos que registra una persona. */
    public const MANUAL = [
        'call' => 'Llamada',
        'email' => 'Correo',
        'whatsapp' => 'WhatsApp',
        'meeting' => 'Reunión',
        'task' => 'Tarea',
        'other' => 'Otro',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
