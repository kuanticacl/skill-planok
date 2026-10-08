<?php

namespace App\Services\Email;

/** Mensaje listo para entregar a un proveedor. */
class OutgoingEmail
{
    /**
     * @param  array<string, string>  $headers
     * @param  array<string, string>  $tags
     */
    public function __construct(
        public string $from,
        public string $to,
        public string $subject,
        public string $html,
        public ?string $text = null,
        public ?string $replyTo = null,
        public array $headers = [],
        public array $tags = [],
    ) {}

    /** @return array<string, mixed> Formato de la API de Resend. */
    public function toResend(): array
    {
        return array_filter([
            'from' => $this->from,
            'to' => [$this->to],
            'subject' => $this->subject,
            'html' => $this->html,
            'text' => $this->text,
            'reply_to' => $this->replyTo,
            'headers' => $this->headers ?: null,
            'tags' => collect($this->tags)->map(fn ($v, $k) => ['name' => $k, 'value' => preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $v)])->values()->all() ?: null,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
