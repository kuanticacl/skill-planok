<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'number', 'title', 'client_id', 'lead_id', 'user_id', 'status', 'recipient', 'sections',
    'issued_at', 'valid_until', 'contract_months', 'discount_type', 'discount_value', 'tax_rate',
    'total_one_time', 'total_monthly', 'total_net', 'total_tax', 'total_gross',
    'internal_notes', 'public_token', 'sent_at', 'viewed_at', 'view_count', 'responded_at', 'responded_by', 'response_note', 'response_ip',
])]
class Proposal extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'draft' => 'Borrador',
        'sent' => 'Enviada',
        'viewed' => 'Vista',
        'accepted' => 'Aceptada',
        'rejected' => 'Rechazada',
        'expired' => 'Vencida',
    ];

    public const STATUS_COLORS = [
        'draft' => '#8A8A8A', 'sent' => '#1AA0E4', 'viewed' => '#6419DB', 'accepted' => '#0D9F85', 'rejected' => '#DC2626', 'expired' => '#C23F00',
    ];

    protected function casts(): array
    {
        return [
            'recipient' => 'array',
            'sections' => 'array',
            'issued_at' => 'date',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Proposal $p) {
            $p->public_token ??= (string) Str::uuid();
            $p->number ??= static::nextNumber();
            $p->issued_at ??= now();
        });
    }

    public static function nextNumber(): string
    {
        $year = now()->year;
        $last = static::withTrashed()->where('number', 'like', "P-{$year}-%")->orderByDesc('number')->value('number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('P-%d-%04d', $year, $seq);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Una propuesta enviada y vencida se considera «Vencida» aunque nadie haya cambiado su estado. */
    public function effectiveStatus(): string
    {
        if (in_array($this->status, ['sent', 'viewed'], true) && $this->valid_until && $this->valid_until->endOfDay()->isPast()) {
            return 'expired';
        }

        return $this->status;
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['accepted', 'rejected'], true);
    }

    /** Sin leads.view_all solo se ven las propias o las de leads asignados al usuario. */
    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, User $user): \Illuminate\Database\Eloquent\Builder
    {
        return $user->hasPermission('leads.view_all')
            ? $query
            : $query->where(fn ($q) => $q->where('user_id', $user->id)->orWhereHas('lead', fn ($l) => $l->where('assigned_to', $user->id)));
    }

    public function publicUrl(): string
    {
        return url('/p/'.$this->public_token);
    }
}
