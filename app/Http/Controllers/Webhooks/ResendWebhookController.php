<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\EmailMessage;
use App\Models\EmailSuppression;
use App\Services\Email\MailSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Eventos de entrega de Resend (https://resend.com/docs/dashboard/webhooks/introduction).
 * Firma Svix: HMAC-SHA256 de "{svix-id}.{svix-timestamp}.{cuerpo}" con la clave base64 del secreto whsec_….
 */
class ResendWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = MailSettings::webhookSecret();

        if (! $secret) {
            return response()->json(['message' => 'Webhook no configurado (falta el signing secret).'], 401);
        }

        if (! self::verify($secret, $request->getContent(), $request->header('svix-id'), $request->header('svix-timestamp'), $request->header('svix-signature'))) {
            return response()->json(['message' => 'Firma inválida.'], 401);
        }

        $event = $request->json()->all();
        $type = (string) ($event['type'] ?? '');
        $data = (array) ($event['data'] ?? []);

        $message = $this->find($data);
        if (! $message) {
            return response()->json(['ok' => true, 'ignored' => 'mensaje desconocido']);
        }

        match ($type) {
            'email.delivered' => $this->delivered($message),
            'email.bounced' => $this->bounced($message, $data),
            'email.complained' => $this->complained($message),
            'email.opened' => $this->opened($message),
            'email.clicked' => $this->clicked($message, $data),
            'email.failed' => $this->failed($message, $data),
            default => null,
        };

        $message->record(str_replace('email.', '', $type) ?: 'unknown', ['source' => 'resend', 'payload' => array_intersect_key($data, array_flip(['bounce', 'click', 'failed', 'reason']))]);

        return response()->json(['ok' => true]);
    }

    public static function verify(string $secret, string $body, ?string $id, ?string $timestamp, ?string $signatures): bool
    {
        if (! $id || ! $timestamp || ! $signatures || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $key = base64_decode(str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret, true);
        if ($key === false) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$body}", $key, true));

        foreach (explode(' ', $signatures) as $candidate) {
            [$version, $sig] = array_pad(explode(',', $candidate, 2), 2, '');
            if ($version === 'v1' && hash_equals($expected, $sig)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $data */
    private function find(array $data): ?EmailMessage
    {
        if (! empty($data['email_id'])) {
            $m = EmailMessage::where('provider_id', $data['email_id'])->first();
            if ($m) {
                return $m;
            }
        }

        $tags = $data['tags'] ?? [];
        $id = is_array($tags) ? ($tags['message_id'] ?? collect($tags)->firstWhere('name', 'message_id')['value'] ?? null) : null;

        return $id ? EmailMessage::find($id) : null;
    }

    private function delivered(EmailMessage $m): void
    {
        if (in_array($m->status, ['queued', 'sending', 'sent'], true)) {
            $m->update(['status' => 'delivered', 'delivered_at' => now()]);
        }
    }

    /** @param array<string, mixed> $data */
    private function bounced(EmailMessage $m, array $data): void
    {
        $type = strtolower((string) ($data['bounce']['type'] ?? 'permanent'));
        $m->update(['status' => 'bounced', 'bounced_at' => now(), 'error' => $data['bounce']['message'] ?? 'Rebotó']);

        if ($type === 'permanent' || $type === '') {
            EmailSuppression::add($m->to_email, 'bounced', 'Rebote permanente', $m->id);
        }
    }

    private function complained(EmailMessage $m): void
    {
        $m->update(['status' => 'complained', 'complained_at' => now()]);
        EmailSuppression::add($m->to_email, 'complained', 'Marcó el correo como spam', $m->id);
    }

    private function opened(EmailMessage $m): void
    {
        if ($m->track_opens) {
            return; // ya medimos con nuestro pixel
        }
        $m->forceFill(['first_opened_at' => $m->first_opened_at ?? now(), 'open_count' => $m->open_count + 1])->save();
    }

    /** @param array<string, mixed> $data */
    private function clicked(EmailMessage $m, array $data): void
    {
        if ($m->track_clicks) {
            return;
        }
        $m->forceFill(['first_clicked_at' => $m->first_clicked_at ?? now(), 'click_count' => $m->click_count + 1])->save();
    }

    /** @param array<string, mixed> $data */
    private function failed(EmailMessage $m, array $data): void
    {
        $m->update(['status' => 'failed', 'error' => $data['failed']['reason'] ?? $data['reason'] ?? 'Falló el envío']);
    }
}
