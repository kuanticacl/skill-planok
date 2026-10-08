<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadField;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeadService
{
    /** Separación entre posiciones del Kanban: permite insertar entre dos tarjetas sin renumerar. */
    private const GAP = 1024;

    /**
     * Crea un lead en la primera etapa (arriba de la columna) y registra el ingreso.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?User $actor = null, array $activity = []): Lead
    {
        return DB::transaction(function () use ($attributes, $actor, $activity) {
            $stageId = $attributes['stage_id'] ?? PipelineStage::initial()?->id;

            $lead = new Lead($attributes);
            $lead->stage_id = $stageId;
            $lead->position = $this->topPosition($stageId);
            $lead->save();

            $this->log($lead, 'created', $actor, 'Lead ingresado'.($lead->source ? " desde {$lead->source->name}" : ''), $activity);

            if ($lead->assigned_to) {
                $this->log($lead, 'assigned', $actor, 'Asignado a '.$lead->assignee?->name, ['to' => $lead->assigned_to]);
            }

            return $lead;
        });
    }

    /**
     * Actualiza datos del lead y deja constancia de qué campos cambiaron.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Lead $lead, array $attributes, ?User $actor = null): Lead
    {
        $oldStage = $lead->stage_id;
        $oldAssignee = $lead->assigned_to;

        $lead->fill($attributes);
        $changed = array_keys($lead->getDirty());
        $lead->save();

        $labels = [
            'first_name' => 'nombre', 'last_name' => 'apellido', 'email' => 'correo', 'phone' => 'teléfono',
            'job_title' => 'cargo', 'company' => 'empresa', 'message' => 'mensaje', 'client_id' => 'cliente',
            'source_id' => 'origen', 'custom' => 'campos personalizados',
        ];

        $edited = collect($changed)->intersect(array_keys($labels))->map(fn ($k) => $labels[$k])->values();
        if ($edited->isNotEmpty()) {
            $this->log($lead, 'updated', $actor, 'Datos actualizados: '.$edited->implode(', '));
        }

        if ($oldStage !== $lead->stage_id) {
            $this->logStageChange($lead, $oldStage, $lead->stage_id, $actor);
        }

        if ($oldAssignee !== $lead->assigned_to) {
            $this->logAssignment($lead, $actor);
        }

        return $lead;
    }

    /** Mueve el lead a una etapa y lo ubica entre dos tarjetas (o arriba si no se indican). */
    public function move(Lead $lead, int $stageId, ?int $afterId = null, ?User $actor = null): Lead
    {
        return DB::transaction(function () use ($lead, $stageId, $afterId, $actor) {
            $oldStage = $lead->stage_id;

            $lead->stage_id = $stageId;
            $lead->position = $this->positionAfter($stageId, $afterId, $lead->id);
            $lead->save();

            if ($oldStage !== $stageId) {
                $this->logStageChange($lead, $oldStage, $stageId, $actor);
            }

            return $lead;
        });
    }

    public function assign(Lead $lead, ?int $userId, ?User $actor = null): Lead
    {
        if ($lead->assigned_to === $userId) {
            return $lead;
        }

        $lead->assigned_to = $userId;
        $lead->save();
        $this->logAssignment($lead, $actor);

        return $lead;
    }

    public function log(Lead $lead, string $type, ?User $actor, ?string $description = null, array $properties = [], $occurredAt = null): LeadActivity
    {
        return $lead->activities()->create([
            'user_id' => $actor?->id,
            'type' => $type,
            'description' => $description,
            'properties' => $properties ?: null,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    private function logStageChange(Lead $lead, ?int $from, int $to, ?User $actor): void
    {
        $names = PipelineStage::whereIn('id', array_filter([$from, $to]))->pluck('name', 'id');

        $this->log(
            $lead,
            'stage_changed',
            $actor,
            'Etapa: '.($names[$from] ?? '—').' → '.($names[$to] ?? '—'),
            ['from' => $from, 'to' => $to],
        );
    }

    private function logAssignment(Lead $lead, ?User $actor): void
    {
        $this->log(
            $lead,
            'assigned',
            $actor,
            $lead->assigned_to ? 'Asignado a '.User::find($lead->assigned_to)?->name : 'Se quitó la asignación',
            ['to' => $lead->assigned_to],
        );
    }

    private function topPosition(int $stageId): int
    {
        $min = Lead::withTrashed()->where('stage_id', $stageId)->min('position');

        return $min === null ? 0 : (int) $min - self::GAP;
    }

    /** Posición para insertar justo después de $afterId (o al inicio de la columna si es null). */
    private function positionAfter(int $stageId, ?int $afterId, int $movingId): int
    {
        $column = fn () => Lead::where('stage_id', $stageId)->where('id', '!=', $movingId);

        if ($afterId === null) {
            $min = $column()->min('position');

            return $min === null ? 0 : (int) $min - self::GAP;
        }

        $after = $column()->whereKey($afterId)->first();
        if (! $after) {
            return $this->positionAfter($stageId, null, $movingId);
        }

        $next = $column()->where('position', '>', $after->position)->orderBy('position')->first();

        if (! $next) {
            return (int) $after->position + self::GAP;
        }

        if ($next->position - $after->position < 2) {
            $this->renumber($stageId);
            $after->refresh();

            return $this->positionAfter($stageId, $afterId, $movingId);
        }

        return (int) floor(($after->position + $next->position) / 2);
    }

    private function renumber(int $stageId): void
    {
        $i = 0;
        Lead::where('stage_id', $stageId)->orderBy('position')->orderBy('id')->get(['id'])->each(function ($lead) use (&$i) {
            Lead::whereKey($lead->id)->update(['position' => $i++ * self::GAP]);
        });
    }

    /**
     * Separa los valores de campos personalizados recibidos de los que no se reconocen.
     *
     * @param  array<string, mixed>  $input
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} [custom, desconocidos]
     */
    public function normalizeCustom(array $input): array
    {
        $fields = LeadField::where('is_active', true)->get()->keyBy('key');
        $custom = [];
        $unknown = [];

        foreach ($input as $key => $value) {
            $field = $fields->get($key);

            if (! $field) {
                $unknown[$key] = $value;

                continue;
            }

            $custom[$key] = match ($field->type) {
                'checkbox' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'number' => is_numeric($value) ? $value + 0 : $value,
                default => is_scalar($value) ? (string) $value : $value,
            };
        }

        return [$custom, $unknown];
    }
}
