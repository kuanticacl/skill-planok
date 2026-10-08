<?php

namespace App\Services\Proposals;

use App\Models\EmailTemplate;
use App\Models\Proposal;
use App\Services\Email\EmailService;
use App\Services\Email\DefaultTemplates;

/** Envía la propuesta por correo (Resend) con el diseño de marca y el enlace público. */
class ProposalMailer
{
    public const SLUG = 'propuesta-comercial';

    public function __construct(private EmailService $emails) {}

    public function send(Proposal $p, string $to, ?string $name, string $subject, string $message, ?int $senderId = null): void
    {
        $this->emails->queueTemplate($this->template(), $to, $name, [
            'first_name' => explode(' ', trim((string) $name))[0] ?? '',
            'proposal_title' => $p->title,
            'proposal_number' => $p->number,
            'proposal_url' => $p->publicUrl(),
            'valid_until' => $p->valid_until?->translatedFormat('d \d\e F \d\e Y') ?? '',
            'message' => $message,
        ], ['subject' => $subject, 'lead_id' => $p->lead_id, 'track_opens' => true]);
    }

    /** Plantilla de marca (editor visual); se crea la primera vez y luego se puede editar en Plantillas. */
    public function template(): EmailTemplate
    {
        return app(DefaultTemplates::class)->ensure(self::SLUG);
    }
}
