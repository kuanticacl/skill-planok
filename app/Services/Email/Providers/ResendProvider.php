<?php

namespace App\Services\Email\Providers;

use App\Services\Email\MailSettings;
use App\Services\Email\OutgoingEmail;
use App\Services\Email\ProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** Resend (https://resend.com/docs/api-reference). */
class ResendProvider implements MailProvider
{
    private const BASE = 'https://api.resend.com';

    public function name(): string
    {
        return 'resend';
    }

    public function send(OutgoingEmail $email): string
    {
        $response = $this->post('/emails', $email->toResend());

        return (string) ($response->json('id') ?? throw new ProviderException('Resend no devolvió un id de envío.', true));
    }

    public function sendBatch(array $emails): array
    {
        $response = $this->post('/emails/batch', array_map(fn (OutgoingEmail $e) => $e->toResend(), $emails));
        $ids = collect($response->json('data') ?? [])->pluck('id')->all();

        if (count($ids) !== count($emails)) {
            throw new ProviderException('Resend devolvió una cantidad de ids distinta a la enviada.', true);
        }

        return $ids;
    }

    /** @param array<mixed> $payload */
    private function post(string $path, array $payload): Response
    {
        $key = MailSettings::apiKey();
        if (! $key) {
            throw new ProviderException('Falta la API key de Resend (Email → Configuración).', false);
        }

        try {
            $response = Http::withToken($key)->acceptJson()->asJson()->timeout(30)->post(self::BASE.$path, $payload);
        } catch (ConnectionException $e) {
            throw new ProviderException('No se pudo conectar con Resend: '.$e->getMessage(), true, 30);
        }

        if ($response->successful()) {
            return $response;
        }

        $message = $response->json('message') ?: $response->json('error.message') ?: ('Error HTTP '.$response->status());
        $status = $response->status();

        if ($status === 429) {
            throw new ProviderException('Límite de envíos de Resend alcanzado: '.$message, true, (int) ($response->header('retry-after') ?: 5), $status);
        }

        if ($status >= 500) {
            throw new ProviderException("Resend no disponible ({$status}): {$message}", true, 30, $status);
        }

        throw new ProviderException($message, false, 0, $status);
    }
}
