<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\LeadField;
use Illuminate\Support\Collection;

class LeadPresenter
{
    /** Datos mínimos para tarjetas (Kanban) y listados. @param Collection<int, LeadField>|null $cardFields */
    public static function card(Lead $lead, ?Collection $cardFields = null): array
    {
        $custom = [];
        foreach ($cardFields ?? [] as $field) {
            $value = $lead->custom[$field->key] ?? null;
            if ($value !== null && $value !== '') {
                $custom[] = ['label' => $field->label, 'value' => is_bool($value) ? ($value ? 'Sí' : 'No') : (string) $value];
            }
        }

        return [
            'id' => $lead->id,
            'full_name' => $lead->full_name,
            'company' => $lead->company,
            'job_title' => $lead->job_title,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'stage_id' => $lead->stage_id,
            'position' => $lead->position,
            'utm_campaign' => $lead->utm_campaign,
            'priority' => $lead->priority,
            'estimated_value' => $lead->estimated_value,
            'tags' => $lead->tags ?? [],
            'next_follow_up_at' => $lead->next_follow_up_at?->toIso8601String(),
            'stage_changed_at' => ($lead->stage_changed_at ?? $lead->created_at)?->toIso8601String(),
            'closed_at' => $lead->closed_at?->toIso8601String(),
            'lost_reason' => $lead->lost_reason,
            'last_activity_at' => $lead->last_activity_at ? \Illuminate\Support\Carbon::parse($lead->last_activity_at)->toIso8601String() : null,
            'source' => $lead->source?->only(['id', 'name', 'color', 'icon']),
            'assignee' => $lead->assignee?->only(['id', 'name']),
            'custom' => $custom,
            'created_at' => $lead->created_at?->toIso8601String(),
        ];
    }
}
