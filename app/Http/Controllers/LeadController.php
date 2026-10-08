<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadRequest;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadField;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\LeadService;
use App\Support\LeadPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function __construct(private LeadService $leads) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['q', 'source', 'stage', 'assignee', 'from', 'to']);

        $leads = Lead::query()
            ->visibleTo($user)
            ->with(['source:id,name,color,icon', 'stage:id,name,color', 'assignee:id,name'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%")))
            ->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source_id', $v))
            ->when($filters['stage'] ?? null, fn ($q, $v) => $q->where('stage_id', $v))
            ->when($filters['assignee'] ?? null, fn ($q, $v) => $v === 'none' ? $q->whereNull('assigned_to') : $q->where('assigned_to', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Lead $lead) => [
                ...LeadPresenter::card($lead),
                'stage' => $lead->stage?->only(['id', 'name', 'color']),
            ]);

        return Inertia::render('leads/Index', [
            'leads' => $leads,
            'filters' => $filters,
            'sources' => LeadSource::orderBy('sort_order')->get(['id', 'name', 'color']),
            'stages' => PipelineStage::orderBy('sort_order')->get(['id', 'name', 'color']),
            'users' => $user->hasPermission('leads.view_all') ? $this->assignableUsers() : [],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('leads/Form', [
            'lead' => null,
            ...$this->formData($request->user()),
            'defaults' => [
                'priority' => 'normal',
                'source_id' => LeadSource::where('slug', LeadSource::MANUAL_SLUG)->value('id'),
                'stage_id' => PipelineStage::initial()?->id,
                'assigned_to' => $request->user()->id,
                'client_id' => $request->integer('client') ?: null,
            ],
        ]);
    }

    public function store(LeadRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $data = $this->payload($request);

        // Sin permiso para asignar, el lead queda a nombre de quien lo crea.
        if (! $user->hasPermission('leads.assign')) {
            $data['assigned_to'] = $user->id;
        }

        $lead = $this->leads->create($data, $user, ['channel' => 'manual']);
        Cache::forget('lead-tags');

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['id' => $lead->id]);
        }

        $this->toast("Lead «{$lead->full_name}» creado.");

        return to_route('leads.show', $lead);
    }

    public function show(Request $request, Lead $lead): Response
    {
        return Inertia::render('leads/Show', $this->detail($request, $lead));
    }

    /** Mismo contenido de la ficha, en JSON, para el panel lateral del Kanban. */
    public function panel(Request $request, Lead $lead): JsonResponse
    {
        return response()->json($this->detail($request, $lead));
    }

    /** @return array<string, mixed> */
    private function detail(Request $request, Lead $lead): array
    {
        $this->authorize('view', $lead);
        $user = $request->user();

        $lead->load(['source', 'stage', 'assignee:id,name,email', 'client:id,name']);

        $fields = LeadField::orderBy('sort_order')->orderBy('id')->get();
        $custom = $fields->map(fn (LeadField $f) => [
            'key' => $f->key,
            'label' => $f->label,
            'type' => $f->type,
            'value' => $lead->custom[$f->key] ?? null,
            'active' => $f->is_active,
        ])->filter(fn ($f) => ($f['value'] !== null && $f['value'] !== '') || $f['active'])->values();

        return [
            'lead' => [
                ...LeadPresenter::card($lead),
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'message' => $lead->message,
                'client' => $lead->client?->only(['id', 'name']),
                'stage' => $lead->stage?->only(['id', 'name', 'color', 'type']),
                'utm' => collect(Lead::UTM_FIELDS)->mapWithKeys(fn ($k) => [$k => $lead->{$k}])->all(),
                'capture' => [
                    'ip_address' => $lead->ip_address,
                    'user_agent' => $lead->user_agent,
                    'referrer' => $lead->referrer,
                    'landing_url' => $lead->landing_url,
                    'country' => $lead->country,
                    'region' => $lead->region,
                    'city' => $lead->city,
                    'latitude' => $lead->latitude,
                    'longitude' => $lead->longitude,
                ],
                'meta' => $lead->meta,
            ],
            'customFields' => $custom,
            'notes' => $lead->notes()->visibleTo($user)->with('author:id,name')->get()->map(fn ($n) => [
                'id' => $n->id,
                'body' => $n->body,
                'is_private' => $n->is_private,
                'author' => $n->author?->name ?? 'Usuario eliminado',
                'mine' => $n->user_id === $user->id,
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'activities' => $lead->activities()->with('user:id,name')->limit(200)->get()->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'description' => $a->description,
                'user' => $a->user?->name,
                'occurred_at' => $a->occurred_at->toIso8601String(),
            ]),
            'priorities' => Lead::PRIORITIES,
            'stages' => PipelineStage::orderBy('sort_order')->get(['id', 'name', 'color', 'type']),
            'users' => $this->assignableUsers(),
            'followUpTypes' => LeadActivity::MANUAL,
            'can' => [
                'update' => $user->can('update', $lead),
                'move' => $user->can('move', $lead),
                'delete' => $user->can('delete', $lead),
                'note' => $user->can('note', $lead),
                'assign' => $user->hasPermission('leads.assign') && $user->can('view', $lead),
            ],
        ];
    }

    public function edit(Request $request, Lead $lead): Response
    {
        $this->authorize('update', $lead);

        return Inertia::render('leads/Form', [
            'lead' => [
                'id' => $lead->id,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'job_title' => $lead->job_title,
                'company' => $lead->company,
                'message' => $lead->message,
                'client_id' => $lead->client_id,
                'source_id' => $lead->source_id,
                'stage_id' => $lead->stage_id,
                'assigned_to' => $lead->assigned_to,
                'priority' => $lead->priority,
                'estimated_value' => $lead->estimated_value,
                'tags' => $lead->tags ?? [],
                'next_follow_up_at' => $lead->next_follow_up_at?->format('Y-m-d\TH:i'),
                'custom' => $lead->custom ?? (object) [],
            ],
            ...$this->formData($request->user()),
            'defaults' => null,
        ]);
    }

    public function update(LeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);
        $user = $request->user();
        $data = $this->payload($request);

        if (! $user->hasPermission('leads.assign')) {
            unset($data['assigned_to']);
        }
        if (! $user->hasPermission('leads.move')) {
            unset($data['stage_id']);
        }
        // Se conservan los valores de campos que hoy están inactivos o eliminados.
        $data['custom'] = [...($lead->custom ?? []), ...($data['custom'] ?? [])] ?: null;

        $this->leads->update($lead, $data, $user);

        $this->toast('Lead actualizado.');

        return to_route('leads.show', $lead);
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        $this->toast('Lead eliminado.');

        return to_route('leads.index');
    }

    /** Cambia de etapa y/o reordena (arrastre en el Kanban o selector en la ficha). */
    public function move(Request $request, Lead $lead): JsonResponse|RedirectResponse
    {
        $this->authorize('move', $lead);

        $data = $request->validate([
            'stage_id' => ['required', 'integer', 'exists:pipeline_stages,id'],
            'after_id' => ['nullable', 'integer'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->leads->move($lead, $data['stage_id'], $data['after_id'] ?? null, $request->user(), [
            'lost_reason' => $data['lost_reason'] ?? null,
            'estimated_value' => $data['estimated_value'] ?? null,
        ]);

        return $this->respond($request, ['position' => $lead->position, 'stage_id' => $lead->stage_id]);
    }

    public function assign(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('view', $lead);
        abort_unless($request->user()->hasPermission('leads.assign'), 403);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $this->leads->assign($lead, $data['assigned_to'] ?? null, $request->user());

        return $this->respond($request, [], 'Responsable actualizado.');
    }

    /** Edición rápida desde el tablero: prioridad, valor, etiquetas y próximo seguimiento. */
    public function quick(Request $request, Lead $lead): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validate([
            'priority' => ['sometimes', Rule::in(array_keys(Lead::PRIORITIES))],
            'estimated_value' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999999'],
            'tags' => ['sometimes', 'nullable', 'array', 'max:15'],
            'tags.*' => ['string', 'max:30'],
            'next_follow_up_at' => ['sometimes', 'nullable', 'date'],
        ]);

        if (array_key_exists('tags', $data)) {
            $data['tags'] = collect($data['tags'] ?? [])->map(fn ($t) => trim($t))->filter()->unique()->values()->all() ?: null;
            Cache::forget('lead-tags');
        }

        $this->leads->update($lead, $data, $request->user());

        return $this->respond($request, [], 'Lead actualizado.');
    }

    /** Acciones masivas sobre los leads seleccionados en el tablero. */
    public function bulk(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['move', 'assign', 'priority', 'add_tag', 'delete'])],
            'stage_id' => ['required_if:action,move', 'nullable', 'integer', 'exists:pipeline_stages,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['required_if:action,priority', 'nullable', Rule::in(array_keys(Lead::PRIORITIES))],
            'tag' => ['required_if:action,add_tag', 'nullable', 'string', 'max:30'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $ability = match ($data['action']) {
            'move' => 'move',
            'delete' => 'delete',
            default => 'update',
        };
        if ($data['action'] === 'assign') {
            abort_unless($user->hasPermission('leads.assign'), 403);
        }

        $leads = Lead::query()->visibleTo($user)->whereIn('id', $data['ids'])->get()->filter(fn (Lead $l) => $user->can($ability, $l));
        $done = 0;

        foreach ($leads as $lead) {
            match ($data['action']) {
                'move' => $this->leads->move($lead, (int) $data['stage_id'], null, $user, ['lost_reason' => $data['lost_reason'] ?? null]),
                'assign' => $this->leads->assign($lead, $data['assigned_to'] ?? null, $user),
                'priority' => $this->leads->update($lead, ['priority' => $data['priority']], $user),
                'add_tag' => $this->leads->update($lead, ['tags' => collect($lead->tags ?? [])->push(trim($data['tag']))->unique()->values()->all()], $user),
                'delete' => $lead->delete(),
            };
            $done++;
        }

        Cache::forget('lead-tags');

        return $this->respond($request, ['done' => $done, 'skipped' => count($data['ids']) - $done], "{$done} leads actualizados.");
    }

    /** @return array<string, mixed> */
    private function payload(LeadRequest $request): array
    {
        $data = $request->safe()->except('custom');
        [$custom] = $this->leads->normalizeCustom($request->input('custom', []));
        $data['custom'] = $custom ?: null;

        if (empty($data['stage_id'])) {
            unset($data['stage_id']); // en alta usa la etapa inicial; en edición conserva la actual
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function formData(User $user): array
    {
        return [
            'sources' => LeadSource::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'stages' => PipelineStage::orderBy('sort_order')->get(['id', 'name']),
            'priorities' => Lead::PRIORITIES,
            'users' => $user->hasPermission('leads.assign') ? $this->assignableUsers() : [],
            'clients' => Client::where('is_active', true)->orderBy('name')->limit(1000)->get(['id', 'name']),
            'fields' => LeadField::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(),
            'can' => [
                'assign' => $user->hasPermission('leads.assign'),
                'move' => $user->hasPermission('leads.move'),
            ],
        ];
    }

    private function assignableUsers()
    {
        return User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }
}
