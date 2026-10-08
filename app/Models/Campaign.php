<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'subject', 'preheader', 'template_id', 'html', 'from_name', 'from_email', 'reply_to', 'audience', 'variables',
    'status', 'scheduled_at', 'started_at', 'finished_at', 'recipients_count', 'track_opens', 'track_clicks', 'created_by',
])]
class Campaign extends Model
{
    public const STATUSES = [
        'draft' => 'Borrador',
        'scheduled' => 'Programado',
        'sending' => 'Enviando',
        'sent' => 'Enviado',
        'paused' => 'Pausado',
        'cancelled' => 'Cancelado',
        'failed' => 'Con errores',
    ];

    protected function casts(): array
    {
        return [
            'audience' => 'array',
            'variables' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled'], true);
    }
}
