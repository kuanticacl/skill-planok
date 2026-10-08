<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\EmailMessage;
use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'status', 'kind', 'campaign', 'template', 'from', 'to']);

        $messages = EmailMessage::query()
            ->with(['campaign:id,name', 'template:id,name'])
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('to_email', 'like', "%{$v}%")->orWhere('subject', 'like', "%{$v}%")))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['kind'] ?? null, fn ($q, $v) => $q->where('kind', $v))
            ->when($filters['campaign'] ?? null, fn ($q, $v) => $q->where('campaign_id', $v))
            ->when($filters['template'] ?? null, fn ($q, $v) => $q->where('template_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('id')->paginate(25)->withQueryString()
            ->through(fn (EmailMessage $m) => [
                ...$m->only(['id', 'kind', 'to_email', 'to_name', 'subject', 'status', 'error', 'open_count', 'click_count', 'created_at', 'sent_at']),
                'campaign' => $m->campaign?->name,
                'template' => $m->template?->name,
            ]);

        return Inertia::render('email/messages/Index', [
            'messages' => $messages,
            'filters' => $filters,
            'statuses' => EmailMessage::STATUSES,
            'kinds' => ['campaign' => 'Boletín', 'transactional' => 'API / automatización', 'test' => 'Prueba'],
            'campaigns' => Campaign::orderByDesc('id')->limit(100)->get(['id', 'name']),
            'templates' => EmailTemplate::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Detalle para el panel lateral: eventos y contenido enviado. */
    public function show(EmailMessage $message): JsonResponse
    {
        $message->load(['events', 'campaign:id,name', 'template:id,name', 'lead:id,first_name,last_name']);

        return response()->json([
            ...$message->only(['id', 'uuid', 'kind', 'to_email', 'to_name', 'subject', 'status', 'provider', 'provider_id', 'error', 'variables', 'open_count', 'click_count', 'sent_at', 'delivered_at', 'first_opened_at', 'first_clicked_at', 'bounced_at', 'unsubscribed_at', 'created_at']),
            'campaign' => $message->campaign?->only(['id', 'name']),
            'template' => $message->template?->only(['id', 'name']),
            'lead' => $message->lead ? ['id' => $message->lead->id, 'name' => $message->lead->full_name] : null,
            'events' => $message->events->map(fn ($e) => ['id' => $e->id, 'type' => $e->type, 'data' => $e->data, 'occurred_at' => $e->occurred_at?->toIso8601String()]),
            'view_url' => route('email.view', $message->uuid),
        ]);
    }
}
