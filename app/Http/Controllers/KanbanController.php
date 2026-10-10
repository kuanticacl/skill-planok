<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadField;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\User;
use App\Support\LeadFilters;
use App\Support\LeadPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class KanbanController extends Controller
{
    private const COLUMN_PAGE = 30;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = LeadFilters::fromRequest($request);

        // Recuentos por origen: respetan los demás filtros pero no el de origen.
        $sourceCounts = $filters->apply(Lead::query(), $user, ['sources'])
            ->selectRaw('source_id, count(*) as total')->groupBy('source_id')->pluck('total', 'source_id');

        $stageAgg = $filters->apply(Lead::query(), $user)
            ->selectRaw('stage_id, count(*) as total, coalesce(sum(estimated_value), 0) as value')
            ->groupBy('stage_id')->get()->keyBy('stage_id');

        $stages = PipelineStage::orderBy('sort_order')->orderBy('id')->get();

        $total = (int) $stageAgg->sum('total');
        $byType = $stages->groupBy('type')->map(fn ($group) => (int) $group->sum(fn ($s) => $stageAgg[$s->id]->total ?? 0));
        $wonValue = (float) $stages->where('type', 'won')->sum(fn ($s) => $stageAgg[$s->id]->value ?? 0);
        $pipelineValue = (float) $stages->where('type', 'open')->sum(fn ($s) => $stageAgg[$s->id]->value ?? 0);
        $totalBySource = (int) $sourceCounts->sum();

        $cardFields = $this->cardFields();

        $board = $stages->map(fn (PipelineStage $stage) => [
            'id' => $stage->id,
            'name' => $stage->name,
            'color' => $stage->color,
            'type' => $stage->type,
            'total' => (int) ($stageAgg[$stage->id]->total ?? 0),
            'value' => (float) ($stageAgg[$stage->id]->value ?? 0),
            'leads' => $this->columnQuery($user, $filters, $stage->id)
                ->limit(self::COLUMN_PAGE)->get()
                ->map(fn (Lead $l) => LeadPresenter::card($l, $cardFields))->values(),
        ]);

        $overdueBase = $filters->apply(Lead::query(), $user)->whereNull('closed_at');

        return Inertia::render('Kanban', [
            'filters' => $filters->values,
            'periods' => LeadFilters::PERIODS,
            'sorts' => LeadFilters::SORTS,
            'priorities' => Lead::PRIORITIES,
            'stats' => [
                'total' => $total,
                'today' => $filters->apply(Lead::query(), $user, ['period'])->where('created_at', '>=', now()->startOfDay())->count(),
                'open' => (int) ($byType['open'] ?? 0),
                'won' => (int) ($byType['won'] ?? 0),
                'lost' => (int) ($byType['lost'] ?? 0),
                'conversion' => $total > 0 ? round(($byType['won'] ?? 0) / $total * 100, 1) : 0,
                'overdue' => (clone $overdueBase)->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now())->count(),
                'pipeline_value' => $pipelineValue,
                'won_value' => $wonValue,
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
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'tags' => $this->knownTags(),
            'manualSourceId' => LeadSource::where('slug', LeadSource::MANUAL_SLUG)->value('id'),
            'stages' => $stages->map(fn ($s) => $s->only(['id', 'name', 'color', 'type']))->values(),
            'can' => [
                'create' => $user->hasPermission('leads.create'),
                'move' => $user->hasPermission('leads.move'),
                'assign' => $user->hasPermission('leads.assign'),
                'update' => $user->hasPermission('leads.update'),
                'delete' => $user->hasPermission('leads.delete'),
                'viewAll' => $user->hasPermission('leads.view_all'),
                'email' => $user->hasPermission('campaigns.send'),
            ],
        ]);
    }

    /** Carga más tarjetas de una columna del Kanban (botón "Cargar más"). */
    public function column(Request $request, PipelineStage $stage): JsonResponse
    {
        $offset = max(0, $request->integer('offset'));
        $filters = LeadFilters::fromRequest($request);

        $leads = $this->columnQuery($request->user(), $filters, $stage->id)
            ->offset($offset)->limit(self::COLUMN_PAGE)->get()
            ->map(fn (Lead $l) => LeadPresenter::card($l, $this->cardFields()))->values();

        return response()->json(['leads' => $leads]);
    }

    private function columnQuery(User $user, LeadFilters $filters, int $stageId): Builder
    {
        $query = $filters->apply(Lead::query(), $user)
            ->where('stage_id', $stageId)
            ->with(['source:id,name,color,icon', 'assignee:id,name', 'latestProposal'])
            ->withCount('proposals')
            ->withMax('activities as last_activity_at', 'occurred_at');

        return $filters->sort($query);
    }

    private function cardFields()
    {
        return LeadField::where('is_active', true)->where('show_on_card', true)->orderBy('sort_order')->get();
    }

    /** @return array<int, string> */
    private function knownTags(): array
    {
        return Cache::remember('lead-tags', 300, fn () => Lead::query()->whereNotNull('tags')->limit(3000)->pluck('tags')
            ->flatten()->filter()->unique()->sort()->values()->all());
    }
}
