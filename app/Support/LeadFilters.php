<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filtros compartidos por el Kanban, el listado de leads y las acciones masivas.
 * Los valores múltiples viajan como lista separada por comas (?sources=1,2).
 */
class LeadFilters
{
    public const PERIODS = [
        'today' => 'Hoy',
        '7' => 'Últimos 7 días',
        '30' => 'Últimos 30 días',
        'month' => 'Este mes',
        '90' => 'Últimos 90 días',
        'all' => 'Todo el tiempo',
    ];

    public const SORTS = [
        'manual' => 'Orden manual',
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguos',
        'value' => 'Mayor valor',
        'followup' => 'Próximo seguimiento',
        'priority' => 'Prioridad',
    ];

    /** @param array<string, mixed> $values */
    public function __construct(public array $values) {}

    public static function fromRequest(Request $request): self
    {
        $period = (string) $request->input('period', 'all');
        $sort = (string) $request->input('sort', 'manual');

        return new self([
            'period' => array_key_exists($period, self::PERIODS) ? $period : 'all',
            'sort' => array_key_exists($sort, self::SORTS) ? $sort : 'manual',
            'q' => self::str($request->input('q')),
            'sources' => self::list($request->input('sources')),
            'assignees' => self::list($request->input('assignees')),
            'priorities' => self::list($request->input('priorities')),
            'tag' => self::str($request->input('tag')),
            'overdue' => $request->boolean('overdue'),
            'no_followup' => $request->boolean('no_followup'),
        ]);
    }

    /** Valores para devolver al front (solo los activos, listas como texto). */
    public function toQuery(): array
    {
        $v = $this->values;

        return array_filter([
            'period' => $v['period'] !== 'all' ? $v['period'] : null,
            'sort' => $v['sort'] !== 'manual' ? $v['sort'] : null,
            'q' => $v['q'],
            'sources' => implode(',', $v['sources']) ?: null,
            'assignees' => implode(',', $v['assignees']) ?: null,
            'priorities' => implode(',', $v['priorities']) ?: null,
            'tag' => $v['tag'],
            'overdue' => $v['overdue'] ? '1' : null,
            'no_followup' => $v['no_followup'] ? '1' : null,
        ], fn ($x) => $x !== null && $x !== '');
    }

    /** Consulta base con visibilidad del usuario y filtros. $except omite filtros (p. ej. 'sources' para los recuentos por origen). */
    public function apply(Builder $query, User $user, array $except = []): Builder
    {
        $v = $this->values;
        $skip = fn (string $k) => in_array($k, $except, true);

        $since = match ($v['period']) {
            'today' => now()->startOfDay(),
            '7' => now()->subDays(7)->startOfDay(),
            '30' => now()->subDays(30)->startOfDay(),
            '90' => now()->subDays(90)->startOfDay(),
            'month' => now()->startOfMonth(),
            default => null,
        };

        $query->visibleTo($user)
            ->when($since && ! $skip('period'), fn ($q) => $q->where('created_at', '>=', $since))
            ->when($v['sources'] && ! $skip('sources'), fn ($q) => $q->whereIn('source_id', $v['sources']))
            ->when($v['priorities'] && ! $skip('priorities'), fn ($q) => $q->whereIn('priority', $v['priorities']))
            ->when($v['tag'] && ! $skip('tag'), fn ($q) => $q->where('tags', 'like', '%"'.str_replace(['%', '_', '"'], '', $v['tag']).'"%'))
            ->when($v['overdue'] && ! $skip('overdue'), fn ($q) => $q->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now())->whereNull('closed_at'))
            ->when($v['no_followup'] && ! $skip('no_followup'), fn ($q) => $q->whereNull('next_follow_up_at')->whereNull('closed_at'))
            ->when($v['q'] && ! $skip('q'), function ($q) use ($v) {
                $term = $v['q'];
                $q->where(fn ($q) => $q
                    ->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('company', 'like', "%{$term}%"));
            });

        // Solo quienes ven todos los leads pueden filtrar por responsable.
        if ($v['assignees'] && ! $skip('assignees') && $user->hasPermission('leads.view_all')) {
            $ids = array_values(array_filter($v['assignees'], fn ($x) => $x !== 'none'));
            $none = in_array('none', $v['assignees'], true);
            $query->where(function ($q) use ($ids, $none) {
                if ($ids) {
                    $q->whereIn('assigned_to', $ids);
                }
                if ($none) {
                    $q->orWhereNull('assigned_to');
                }
            });
        }

        return $query;
    }

    public function sort(Builder $query): Builder
    {
        return match ($this->values['sort']) {
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'value' => $query->orderByRaw('estimated_value IS NULL')->orderByDesc('estimated_value')->orderByDesc('id'),
            'followup' => $query->orderByRaw('next_follow_up_at IS NULL')->orderBy('next_follow_up_at')->orderByDesc('id'),
            'priority' => $query->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")->orderByDesc('id'),
            default => $query->orderBy('position')->orderByDesc('id'),
        };
    }

    /** @return array<int, string> */
    private static function list(mixed $value): array
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn ($x) => $x !== ''));
    }

    private static function str(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
