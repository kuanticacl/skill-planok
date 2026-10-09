<?php

namespace App\Jobs;

use App\Models\EmailMessage;
use App\Models\EmailSuppression;
use App\Services\Email\CampaignRunner;
use App\Services\Email\EmailComposer;
use App\Services\Email\ProviderException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Envía un mensaje individual (API transaccional, automatizaciones y pruebas). */
class SendEmailMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 6;

    public function __construct(public int $messageId) {}

    public function handle(EmailComposer $composer): void
    {
        $message = EmailMessage::find($this->messageId);

        if (! $message || $message->status !== 'queued') {
            return;
        }

        if ($message->kind !== 'test' && EmailSuppression::isSuppressed($message->to_email)) {
            $message->update(['status' => 'suppressed', 'error' => 'El destinatario está en la lista de bajas/rebotes.']);
            $this->redact($message);
            $message->record('suppressed');

            return;
        }

        $message->update(['status' => 'sending']);

        try {
            $built = $composer->build($message);
            $provider = CampaignRunner::provider();
            $id = $provider->send($built['email']);

            $message->update([
                'status' => 'sent',
                'provider' => $provider->name(),
                'provider_id' => $id,
                'sent_at' => now(),
                'subject' => $built['subject'],
                'html' => $built['html'], // copia exacta de lo enviado (auditoría)
                'error' => null,
            ]);
            $this->redact($message);
            $message->record('sent', ['missing_variables' => $built['missing']]);
        } catch (ProviderException $e) {
            if ($e->retryable && $this->attempts() < $this->tries) {
                $message->update(['status' => 'queued', 'error' => $e->getMessage()]);
                $this->release($e->retryAfter ?: 30);

                return;
            }
            $this->fail409($message, $e->getMessage());
        } catch (\Throwable $e) {
            $this->fail409($message, $e->getMessage());
        }
    }

    /** Oculta contraseñas y enlaces de un solo uso del historial (variables y copia del HTML). */
    private function redact(EmailMessage $message): void
    {
        $vars = $message->variables ?? [];
        $keys = (array) ($vars['_redact'] ?? []);
        if (! $keys) {
            return;
        }

        $html = (string) $message->html;
        foreach ($keys as $key) {
            if (filled($vars[$key] ?? null)) {
                $html = str_replace([e((string) $vars[$key]), (string) $vars[$key]], '••••••••', $html);
                $vars[$key] = '••••••••';
            }
        }
        $message->update(['variables' => $vars, 'html' => $html]);
    }

    public function failed(\Throwable $e): void
    {
        EmailMessage::whereKey($this->messageId)->whereIn('status', ['queued', 'sending'])->update(['status' => 'failed', 'error' => $e->getMessage()]);
        if ($message = EmailMessage::find($this->messageId)) {
            $this->redact($message);
        }
    }

    private function fail409(EmailMessage $message, string $error): void
    {
        $message->update(['status' => 'failed', 'error' => $error]);
        $this->redact($message);
        $message->record('failed', ['error' => $error]);
    }
}
