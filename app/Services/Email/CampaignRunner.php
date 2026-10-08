<?php

namespace App\Services\Email;

use App\Jobs\SendCampaignBatch;
use App\Models\Campaign;
use App\Models\EmailMessage;
use App\Services\Email\Providers\LogProvider;
use App\Services\Email\Providers\MailProvider;
use App\Services\Email\Providers\ResendProvider;

/** Expande la audiencia de un boletín en mensajes y los envía por lotes. */
class CampaignRunner
{
    public const BATCH_SIZE = 50;

    public function __construct(private AudienceBuilder $audience, private EmailComposer $composer) {}

    public static function provider(): MailProvider
    {
        return MailSettings::provider() === 'resend' ? new ResendProvider : new LogProvider;
    }

    /** Congela el contenido, crea un mensaje por destinatario y arranca el envío. */
    public function start(Campaign $campaign): void
    {
        $campaign->refresh();

        if (! in_array($campaign->status, ['draft', 'scheduled', 'paused'], true)) {
            return;
        }

        if ($campaign->status !== 'paused') {
            $campaign->loadMissing('template');
            // El contenido se congela: editar la plantilla después no altera lo que se envió.
            $campaign->html = $campaign->html ?: (string) $campaign->template?->html;

            $total = 0;
            $batch = [];
            $now = now();
            foreach ($this->audience->recipients($campaign->audience ?? []) as $r) {
                $batch[] = EmailMessage::make([
                    'campaign_id' => $campaign->id,
                    'template_id' => $campaign->template_id,
                    'kind' => 'campaign',
                    'to_email' => $r['email'],
                    'to_name' => $r['name'],
                    'lead_id' => $r['lead_id'],
                    'client_id' => $r['client_id'],
                    'from_email' => $campaign->from_email,
                    'variables' => [...($campaign->variables ?? []), ...$r['vars']],
                    'status' => 'queued',
                    'track_opens' => $campaign->track_opens,
                    'track_clicks' => $campaign->track_clicks,
                ])->forceFill(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'created_at' => $now, 'updated_at' => $now]);
                $total++;

                if (count($batch) >= 500) {
                    $this->insert($batch);
                    $batch = [];
                }
            }
            $this->insert($batch);

            $campaign->recipients_count = $total;
            $campaign->started_at = now();
        }

        $campaign->status = 'sending';
        $campaign->save();

        SendCampaignBatch::dispatch($campaign->id);
    }

    /** @param array<int, EmailMessage> $messages */
    private function insert(array $messages): void
    {
        if (! $messages) {
            return;
        }

        EmailMessage::insert(array_map(function (EmailMessage $m) {
            $row = $m->getAttributes();
            foreach (['variables'] as $json) {
                if (isset($row[$json]) && is_array($row[$json])) {
                    $row[$json] = json_encode($row[$json], JSON_UNESCAPED_UNICODE);
                }
            }

            return $row;
        }, $messages));
    }

    /**
     * Envía el siguiente lote. Devuelve true si quedan mensajes pendientes.
     *
     * @throws ProviderException si el proveedor pide reintentar (429 / 5xx)
     */
    public function processBatch(Campaign $campaign): bool
    {
        $campaign->refresh();
        if ($campaign->status !== 'sending') {
            return false;
        }

        $ids = EmailMessage::where('campaign_id', $campaign->id)->where('status', 'queued')->orderBy('id')->limit(self::BATCH_SIZE)->pluck('id');

        if ($ids->isEmpty()) {
            $this->finish($campaign);

            return false;
        }

        // Reserva atómica: evita que dos workers tomen los mismos mensajes.
        EmailMessage::whereIn('id', $ids)->where('status', 'queued')->update(['status' => 'sending']);
        $messages = EmailMessage::whereIn('id', $ids)->where('status', 'sending')->get();

        $built = [];
        foreach ($messages as $m) {
            if (\App\Models\EmailSuppression::isSuppressed($m->to_email)) {
                $m->update(['status' => 'suppressed']);

                continue;
            }
            try {
                $built[$m->id] = ['m' => $m, 'b' => $this->composer->build($m)];
            } catch (\Throwable $e) {
                $m->update(['status' => 'failed', 'error' => 'Error al armar el correo: '.$e->getMessage()]);
            }
        }

        if ($built) {
            $this->deliver($built);
        }

        return true;
    }

    /** @param array<int, array{m: EmailMessage, b: array<string, mixed>}> $built */
    private function deliver(array $built): void
    {
        $provider = self::provider();
        $outgoing = array_map(fn ($x) => $x['b']['email'], array_values($built));
        $models = array_column(array_values($built), 'm');

        try {
            $ids = $provider->sendBatch($outgoing);
            foreach ($models as $i => $m) {
                $this->markSent($m, $provider->name(), $ids[$i], $outgoing[$i]->subject);
            }
        } catch (ProviderException $e) {
            if ($e->retryable) {
                // vuelven a la cola y el job se reintenta más tarde
                EmailMessage::whereIn('id', array_map(fn ($m) => $m->id, $models))->update(['status' => 'queued']);
                throw $e;
            }

            // Error permanente en el lote (p. ej. una dirección inválida): se aísla enviando de a uno.
            foreach ($models as $i => $m) {
                try {
                    $this->markSent($m, $provider->name(), $provider->send($outgoing[$i]), $outgoing[$i]->subject);
                } catch (ProviderException $single) {
                    if ($single->retryable) {
                        $m->update(['status' => 'queued']);
                        throw $single;
                    }
                    $m->update(['status' => 'failed', 'error' => $single->getMessage()]);
                    $m->record('failed', ['error' => $single->getMessage()]);
                }
            }
        }

        // Resend limita a pocas solicitudes por segundo.
        if ($provider->name() === 'resend') {
            usleep(600_000);
        }
    }

    private function markSent(EmailMessage $m, string $provider, string $providerId, ?string $subject = null): void
    {
        $m->update(['subject' => $subject, 'status' => 'sent', 'provider' => $provider, 'provider_id' => $providerId, 'sent_at' => now(), 'error' => null]);
        $m->record('sent');
    }

    private function finish(Campaign $campaign): void
    {
        $failed = EmailMessage::where('campaign_id', $campaign->id)->where('status', 'failed')->count();
        $sent = EmailMessage::where('campaign_id', $campaign->id)->whereIn('status', ['sent', 'delivered', 'bounced', 'complained'])->count();

        $campaign->update([
            'status' => $sent === 0 && $failed > 0 ? 'failed' : 'sent',
            'finished_at' => now(),
        ]);
    }
}
