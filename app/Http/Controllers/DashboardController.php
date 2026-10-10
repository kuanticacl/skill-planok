<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\Proposal;
use App\Support\LeadFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/** Resumen comercial: KPIs, embudo, actividad, seguimientos pendientes, orígenes y propuestas. El tablero vive en KanbanController. */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = LeadFilters::fromRequest($request);
        $base = fn (array $skip = []) => $filters->apply(Lead::query(), $user, $skip);

        $stages = PipelineStage::orderBy('sort_order')->orderBy('id')->get();
        $agg = $base()->selectRaw('stage_id, count(*) as total, coalesce(sum(estimated_value), 0) as value')->groupBy('stage_id')->get()->keyBy('stage_id');
        $total = (int) $agg->sum('total');

        $byType = $stages->groupBy('type')->map(fn ($g) => (int) $g->sum(fn ($s) => $agg[$s->id]->total ?? 0));
        $value = fn (string $type) => (float) $stages->where('type', $type)->sum(fn ($s) => $agg[$s->id]->value ?? 0);
        $openBase = fn () => $base()->whereNull('closed_at');
        $now = now();

        $funnel = $stages->map(fn (PipelineStage $s) => [
            'id' => $s->id, 'name' => $s->name, 'color' => $s->color, 'type' => $s->type,
            'total' => (int) ($agg[$s->id]->total ?? 0), 'value' => (float) ($agg[$s->id]->value ?? 0),
        ])->values();

        $sourceCounts = $base(['sources'])->selectRaw('source_id, count(*) as total')->groupBy('source_id')->pluck('total', 'source_id');
        $sourceTotal = max(1, (int) $sourceCounts->sum());

        // Leads creados por día (últimos 14 días, respetando visibilidad pero no el período).
        $from = $now->copy()->startOfDay()->subDays(13);
        $perDay = Lead::query()->visibleTo($user)->where('created_at', '>=', $from)
            ->selectRaw('date(created_at) as d, count(*) as n')->groupBy('d')->pluck('n', 'd');
        $daily = collect(range(0, 13))->map(fn ($i) => ['date' => $from->copy()->addDays($i)->toDateString(), 'leads' => (int) ($perDay[$from->copy()->addDays($i)->toDateString()] ?? 0)]);

        $followUps = $openBase()->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', $now->copy()->endOfDay())
            ->with(['stage:id,name,color', 'assignee:id,name'])->orderBy('next_follow_up_at')->limit(8)->get()
            ->map(fn (Lead $l) => ['id' => $l->id, 'name' => $l->full_name, 'company' => $l->company, 'stage' => $l->stage?->name, 'color' => $l->stage?->color, 'assignee' => $l->assignee?->name, 'due' => $l->next_follow_up_at->toIso8601String(), 'overdue' => $l->next_follow_up_at->isPast()]);

        $recent = Lead::query()->visibleTo($user)->with(['source:id,name,color,icon', 'stage:id,name,color'])->latest('id')->limit(8)->get()
            ->map(fn (Lead $l) => ['id' => $l->id, 'name' => $l->full_name, 'company' => $l->company, 'source' => $l->source?->name, 'source_color' => $l->source?->color, 'stage' => $l->stage?->name, 'color' => $l->stage?->color, 'score_grade' => $l->score_grade, 'created_at' => $l->created_at->toIso8601String()]);

        $proposals = null;
        if ($user->hasPermission('proposals.view')) {
            $all = Proposal::query()->visibleTo($user)->with(['client:id,name'])->latest('id')->limit(300)->get();
            $counts = $all->groupBy(fn (Proposal $p) => $p->effectiveStatus())->map->count();
            $proposals = [
                'counts' => collect(Proposal::STATUSES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'color' => Proposal::STATUS_COLORS[$key] ?? '#8A8A8A', 'count' => (int) ($counts[$key] ?? 0)])->values(),
                'attention' => $all->filter(fn (Proposal $p) => in_array($p->effectiveStatus(), ['changes_requested', 'viewed', 'accepted'], true))->sortByDesc(fn (Proposal $p) => $p->responded_at ?? $p->viewed_at ?? $p->updated_at)->take(6)
                    ->map(fn (Proposal $p) => ['id' => $p->id, 'number' => $p->number, 'title' => $p->title, 'company' => $p->recipient['company'] ?? $p->client?->name, 'status' => $p->effectiveStatus(), 'status_label' => Proposal::STATUSES[$p->effectiveStatus()], 'color' => Proposal::STATUS_COLORS[$p->effectiveStatus()] ?? '#8A8A8A', 'at' => ($p->responded_at ?? $p->viewed_at ?? $p->updated_at)?->toIso8601String()])->values(),
            ];
        }

        return Inertia::render('Dashboard', [
            'filters' => ['period' => $filters->values['period']],
            'periods' => LeadFilters::PERIODS,
            'stats' => [
                'total' => $total,
                'today' => $base(['period'])->where('created_at', '>=', $now->copy()->startOfDay())->count(),
                'open' => (int) ($byType['open'] ?? 0),
                'won' => (int) ($byType['won'] ?? 0),
                'lost' => (int) ($byType['lost'] ?? 0),
                'conversion' => $total > 0 ? round(($byType['won'] ?? 0) / $total * 100, 1) : 0,
                'overdue' => (clone $openBase())->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', $now)->count(),
                'pipeline_value' => $value('open'),
                'won_value' => $value('won'),
            ],
            'funnel' => $funnel,
            'daily' => $daily,
            'sources' => LeadSource::orderBy('sort_order')->get()
                ->map(fn (LeadSource $s) => ['id' => $s->id, 'name' => $s->name, 'color' => $s->color, 'icon' => $s->icon, 'is_active' => $s->is_active, 'count' => (int) ($sourceCounts[$s->id] ?? 0), 'pct' => (int) round(($sourceCounts[$s->id] ?? 0) / $sourceTotal * 100)])
                ->filter(fn ($s) => $s['is_active'] || $s['count'] > 0)->values(),
            'followUps' => $followUps,
            'recent' => $recent,
            'proposals' => $proposals,
            'can' => ['create' => $user->hasPermission('leads.create'), 'leads' => $user->hasPermission('leads.view'), 'proposals' => $user->hasPermission('proposals.view')],
        ]);
    }
}
