<?php

namespace App\Services\Email\Providers;

use App\Services\Email\OutgoingEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Modo de prueba: no envía nada, solo registra en el log. Se usa mientras no haya API key de Resend. */
class LogProvider implements MailProvider
{
    public function name(): string
    {
        return 'log';
    }

    public function send(OutgoingEmail $email): string
    {
        Log::info('[email:log] '.$email->to.' — '.$email->subject, ['from' => $email->from, 'headers' => $email->headers, 'tags' => $email->tags]);

        return 'log_'.Str::uuid();
    }

    public function sendBatch(array $emails): array
    {
        return array_map(fn (OutgoingEmail $e) => $this->send($e), $emails);
    }
}
