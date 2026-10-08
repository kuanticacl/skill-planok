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

    public function failed(\Throwable $e): void
    {
        EmailMessage::whereKey($this->messageId)->whereIn('status', ['queued', 'sending'])->update(['status' => 'failed', 'error' => $e->getMessage()]);
    }

    private function fail409(EmailMessage $message, string $error): void
    {
        $message->update(['status' => 'failed', 'error' => $error]);
        $message->record('failed', ['error' => $error]);
    }
}
