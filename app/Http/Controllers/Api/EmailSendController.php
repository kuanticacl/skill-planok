<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\EmailMessage;
use App\Models\EmailSuppression;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Services\Email\AudienceBuilder;
use App\Services\Email\EmailService;
use App\Services\Email\TemplateRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * POST /api/v1/emails/send — envía una plantilla a una o varias personas.
 * Los datos pueden venir en JSON, formulario o query string; cualquier parámetro que no sea
 * reservado se toma como variable de la plantilla.
 */
class EmailSendController extends Controller
{
    private const RESERVED = ['template', 'template_id', 'to', 'to_name', 'variables', 'subject', 'lead_id', 'delay_minutes', 'send_at', 'track_opens', 'track_clicks', 'from_email', 'api_key', 'email'];

    public function send(Request $request, EmailService $emails, TemplateRenderer $renderer): JsonResponse
    {
        /** @var ApiKey $key */
        $key = $request->attributes->get('api_key');

        // Idempotencia: la misma llamada repetida (mismo header) no envía dos veces.
        $idem = $request->header('Idempotency-Key');
        if ($idem && ($cached = Cache::get("api-idem:{$key->id}:".sha1($idem)))) {
            return response()->json($cached, 202, ['Idempotent-Replay' => 'true']);
        }

        $template = $this->template($request);

        $recipients = $this->recipients($request);
        if (! $recipients) {
            return response()->json(['message' => 'Indica al menos un destinatario válido en «to».', 'errors' => ['to' => ['Falta el destinatario o el correo no es válido.']]], 422);
        }
        if (count($recipients) > 50) {
            return response()->json(['message' => 'Máximo 50 destinatarios por llamada.', 'errors' => ['to' => ['Máximo 50.']]], 422);
        }

        $request->validate([
            'subject' => ['nullable', 'string', 'max:300'],
            'delay_minutes' => ['nullable', 'integer', 'min:0', 'max:43200'],
            'send_at' => ['nullable', 'date', 'after:now'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'from_email' => ['nullable', 'email:rfc'],
        ]);

        $lead = $request->integer('lead_id') ? Lead::with(['source:id,name', 'stage:id,name'])->find($request->integer('lead_id')) : null;
        $base = [...$this->defaults($template), ...($lead ? AudienceBuilder::leadVariables($lead) : []), ...$this->variables($request)];

        $data = [];
        foreach ($recipients as $r) {
            $vars = [...$base, 'to_email' => $r['email'], 'to_name' => $r['name'], 'email' => $base['email'] ?? $r['email'], 'name' => $base['name'] ?? $r['name']];

            // Avisa qué variables faltaron (solo informativo: se envían vacías).
            $renderer->render($template->subject."\n".$template->html, [...$vars, 'unsubscribe_url' => 'x', 'view_url' => 'x']);
            $missing = array_values(array_diff($renderer->missing(), TemplateRenderer::SYSTEM_VARIABLES));

            if (EmailSuppression::isSuppressed($r['email'])) {
                $m = EmailMessage::create(['template_id' => $template->id, 'kind' => 'transactional', 'to_email' => $r['email'], 'to_name' => $r['name'], 'api_key_id' => $key->id, 'lead_id' => $lead?->id, 'variables' => $vars, 'status' => 'suppressed', 'error' => 'El destinatario está en la lista de bajas/rebotes.']);
            } else {
                $m = $emails->queueTemplate($template, $r['email'], $r['name'], $vars, [
                    'subject' => $request->input('subject'),
                    'from_email' => $request->input('from_email'),
                    'lead_id' => $lead?->id,
                    'api_key_id' => $key->id,
                    'track_opens' => $request->boolean('track_opens'),
                    'track_clicks' => $request->boolean('track_clicks'),
                    'delay_minutes' => $request->integer('delay_minutes'),
                    'scheduled_at' => $request->filled('send_at') ? Carbon::parse($request->input('send_at')) : null,
                ]);
            }

            $data[] = ['id' => $m->uuid, 'to' => $m->to_email, 'status' => $m->status, 'scheduled_at' => $m->scheduled_at?->toIso8601String(), 'warnings' => ['missing_variables' => $missing]];
        }

        $response = ['data' => $data];
        if ($idem) {
            Cache::put("api-idem:{$key->id}:".sha1($idem), $response, now()->addDay());
        }

        return response()->json($response, 202);
    }

    public function show(string $uuid): JsonResponse
    {
        $m = EmailMessage::with('events')->where('uuid', $uuid)->firstOrFail();

        return response()->json(['data' => [
            'id' => $m->uuid,
            'to' => $m->to_email,
            'subject' => $m->subject,
            'status' => $m->status,
            'error' => $m->error,
            'scheduled_at' => $m->scheduled_at?->toIso8601String(),
            'sent_at' => $m->sent_at?->toIso8601String(),
            'delivered_at' => $m->delivered_at?->toIso8601String(),
            'opened' => $m->first_opened_at !== null,
            'open_count' => $m->open_count,
            'click_count' => $m->click_count,
            'events' => $m->events->map(fn ($e) => ['type' => $e->type, 'at' => $e->occurred_at?->toIso8601String()]),
        ]]);
    }

    // -------------------------------------------------------------------------------------------

    private function template(Request $request): EmailTemplate
    {
        $query = EmailTemplate::query()->where('is_active', true);
        $template = $request->filled('template_id')
            ? $query->find($request->input('template_id'))
            : ($request->filled('template') ? $query->where('slug', $request->input('template'))->first() : null);

        abort_unless($template, 404, 'Plantilla no encontrada o inactiva. Usa «template» (slug) o «template_id».');

        return $template;
    }

    /** @return array<int, array{email: string, name: ?string}> */
    private function recipients(Request $request): array
    {
        $to = $request->input('to', $request->input('email'));
        $list = is_array($to) && array_is_list($to) ? $to : [$to];
        $out = [];

        foreach ($list as $item) {
            $email = is_array($item) ? ($item['email'] ?? null) : $item;
            $name = is_array($item) ? ($item['name'] ?? null) : ($request->input('to_name') ?: null);
            $email = strtolower(trim((string) $email));

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out[$email] = ['email' => $email, 'name' => $name ? trim((string) $name) : null];
            }
        }

        return array_values($out);
    }

    /** @return array<string, mixed> Variables: objeto «variables» + parámetros sueltos. */
    private function variables(Request $request): array
    {
        $loose = Arr::except($request->all(), self::RESERVED);
        // si «email» se usó como destinatario no es variable; si «to» existe, «email» sí puede serlo
        if ($request->has('to') && $request->has('email')) {
            $loose['email'] = $request->input('email');
        }

        return [...$loose, ...(array) $request->input('variables', [])];
    }

    /** @return array<string, mixed> Valores por defecto declarados en la plantilla. */
    private function defaults(EmailTemplate $t): array
    {
        return collect($t->variables ?? [])->filter(fn ($v) => ($v['default'] ?? '') !== '')->mapWithKeys(fn ($v) => [$v['key'] => $v['default']])->all();
    }
}
