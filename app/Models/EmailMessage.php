<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid', 'campaign_id', 'template_id', 'automation_id', 'api_key_id', 'lead_id', 'client_id', 'kind', 'to_email', 'to_name',
    'from_email', 'subject', 'status', 'provider', 'provider_id', 'error', 'variables', 'html', 'track_opens', 'track_clicks',
    'scheduled_at', 'sent_at', 'delivered_at', 'first_opened_at', 'first_clicked_at', 'open_count', 'click_count',
    'bounced_at', 'complained_at', 'unsubscribed_at',
])]
class EmailMessage extends Model
{
    public const STATUSES = [
        'queued' => 'En cola',
        'sending' => 'Enviando',
        'sent' => 'Enviado',
        'delivered' => 'Entregado',
        'bounced' => 'Rebotado',
        'complained' => 'Spam',
        'failed' => 'Fallido',
        'suppressed' => 'Omitido',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->uuid ??= (string) Str::uuid();
            $m->to_email = strtolower(trim($m->to_email));
        });
    }

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'first_opened_at' => 'datetime',
            'first_clicked_at' => 'datetime',
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class)->orderBy('occurred_at');
    }

    public function record(string $type, array $data = []): EmailEvent
    {
        return $this->events()->create(['type' => $type, 'data' => $data ?: null, 'occurred_at' => now()]);
    }
}
