<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadField;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\User;
use App\Support\LeadPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const COLUMN_PAGE = 30;

    public const PERIODS = [
        'today' => 'Hoy',
        '7' => 'Últimos 7 días',
        '30' => 'Últimos 30 días',
        'month' => 'Este mes',
        '90' => 'Últimos 90 días',
        'all' => 'Todo el tiempo',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $this->filters($request);

        // Recuentos por origen: respetan período/responsable/búsqueda pero no el filtro de origen.
        $sourceCounts = $this->base($user, $filters, withSource: false)
            ->selectRaw('source_id, count(*) as total')->groupBy('source_id')->pluck('total', 'source_id');

        $stageCounts = $this->base($user, $filters)
            ->selectRaw('stage_id, count(*) as total')->groupBy('stage_id')->pluck('total', 'stage_id');

        $stages = PipelineStage::orderBy('sort_order')->orderBy('id')->get();

        $totalBySource = (int) $sourceCounts->sum();
        $byType = $stages->groupBy('type')->map(fn ($group) => (int) $group->sum(fn ($s) => $stageCounts[$s->id] ?? 0));
        $total = (int) $stageCounts->sum();
        $closed = ($byType['won'] ?? 0) + ($byType['lost'] ?? 0);

        $cardFields = LeadField::where('is_active', true)->where('show_on_card', true)->orderBy('sort_order')->get();

        $board = $stages->map(fn (PipelineStage $stage) => [
            'id' => $stage->id,
            'name' => $stage->name,
            'color' => $stage->color,
            'type' => $stage->type,
            'total' => (int) ($stageCounts[$stage->id] ?? 0),
            'leads' => $this->columnQuery($user, $filters, $stage->id)
                ->limit(self::COLUMN_PAGE)->get()
                ->map(fn (Lead $l) => LeadPresenter::card($l, $cardFields))->values(),
        ]);

        return Inertia::render('Dashboard', [
            'filters' => $filters,
            'periods' => self::PERIODS,
            'stats' => [
                'total' => $total,
                'today' => $this->base($user, [...$filters, 'period' => 'today'])->count(),
                'open' => (int) ($byType['open'] ?? 0),
                'won' => (int) ($byType['won'] ?? 0),
                'lost' => (int) ($byType['lost'] ?? 0),
                'conversion' => $total > 0 ? round(($byType['won'] ?? 0) / $total * 100, 1) : 0,
                'win_rate_closed' => $closed > 0 ? round(($byType['won'] ?? 0) / $closed * 100, 1) : 0,
            ],
            'sources' => LeadSource::orderBy('sort_order')->get()
                ->map(fn (LeadSource $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'color' => $s->color,
                    'icon' => $s->icon,
                    'is_active' => $s->is_active,
                    'count' => (int) ($sourceCounts[$s->id] ?? 0),
                    'pct' => $totalBySource > 0 ? round(($sourceCounts[$s->id] ?? 0) / $totalBySource * 100) : 0,
                ])
                ->filter(fn ($s) => $s['is_active'] || $s['count'] > 0)->values(),
            'board' => $board,
            'columnPage' => self::COLUMN_PAGE,
            'users' => $user->hasPermission('leads.view_all')
                ? User::where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : [],
            'can' => [
                'create' => $user->hasPermission('leads.create'),
                'move' => $user->hasPermission('leads.move'),
                'viewAll' => $user->hasPermission('leads.view_all'),
            ],
        ]);
    }

    /** Carga más tarjetas de una columna del Kanban (botón "Cargar más"). */
    public function column(Request $request, PipelineStage $stage): JsonResponse
    {
        $user = $request->user();
        $offset = max(0, $request->integer('offset'));

        $cardFields = LeadField::where('is_active', true)->where('show_on_card', true)->orderBy('sort_order')->get();

        $leads = $this->columnQuery($user, $this->filters($request), $stage->id)
            ->offset($offset)->limit(self::COLUMN_PAGE)->get()
            ->map(fn (Lead $l) => LeadPresenter::card($l, $cardFields))->values();

        return response()->json(['leads' => $leads]);
    }

    /** @return array{period: string, source: ?string, assignee: ?string, q: ?string} */
    private function filters(Request $request): array
    {
        $period = (string) $request->query('period', 'all');

        return [
            'period' => array_key_exists($period, self::PERIODS) ? $period : 'all',
            'source' => $request->query('source') ?: null,
            'assignee' => $request->query('assignee') ?: null,
            'q' => $request->query('q') ?: null,
        ];
    }

    /** @param array<string, mixed> $filters */
    private function base(User $user, array $filters, bool $withSource = true): Builder
    {
        $since = match ($filters['period']) {
            'today' => now()->startOfDay(),
            '7' => now()->subDays(7)->startOfDay(),
            '30' => now()->subDays(30)->startOfDay(),
            '90' => now()->subDays(90)->startOfDay(),
            'month' => now()->startOfMonth(),
            default => null,
        };

        return Lead::query()
            ->visibleTo($user)
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->when($withSource && $filters['source'], fn ($q) => $q->where('source_id', $filters['source']))
            ->when($filters['assignee'], function ($q, $v) use ($user) {
                // Solo quienes ven todos los leads pueden filtrar por otros responsables.
                if (! $user->hasPermission('leads.view_all')) {
                    return $q;
                }

                return $v === 'none' ? $q->whereNull('assigned_to') : $q->where('assigned_to', $v);
            })
            ->when($filters['q'], fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%")));
    }

    /** @param array<string, mixed> $filters */
    private function columnQuery(User $user, array $filters, int $stageId): Builder
    {
        return $this->base($user, $filters)
            ->where('stage_id', $stageId)
            ->with(['source:id,name,color,icon', 'assignee:id,name'])
            ->orderBy('position')->orderByDesc('id');
    }
}
