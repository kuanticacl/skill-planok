<?php

namespace App\Services\Proposals;

use App\Models\EmailTemplate;
use App\Models\Proposal;
use App\Services\Ai\BrandedEmailDesign;
use App\Services\Email\EmailService;
use App\Services\Email\MailSettings;

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

    /** Plantilla transaccional de marca; se crea la primera vez y luego se puede editar en Plantillas. */
    public function template(): EmailTemplate
    {
        return EmailTemplate::firstOrCreate(['slug' => self::SLUG], [
            'name' => 'Propuesta comercial',
            'description' => 'Correo con el enlace a la propuesta comercial (se usa al enviar desde Propuestas).',
            'category' => 'transactional',
            'subject' => 'Propuesta comercial: {{ proposal_title }}',
            'preheader' => 'Revisa tu propuesta de Quiebre',
            'editor' => 'html',
            'html' => $this->html(),
            'is_active' => true,
            'variables' => [
                ['key' => 'first_name', 'label' => 'Nombre', 'default' => '', 'sample' => 'María'],
                ['key' => 'proposal_title', 'label' => 'Título de la propuesta', 'default' => '', 'sample' => 'Marketing digital Proyecto Torre Norte'],
                ['key' => 'proposal_number', 'label' => 'Número', 'default' => '', 'sample' => 'P-2026-0001'],
                ['key' => 'proposal_url', 'label' => 'Enlace', 'default' => '', 'sample' => 'https://www.quiebre.cl'],
                ['key' => 'valid_until', 'label' => 'Válida hasta', 'default' => '', 'sample' => '30 de octubre de 2026'],
                ['key' => 'message', 'label' => 'Mensaje', 'default' => '', 'sample' => 'Adjunto la propuesta que conversamos.'],
            ],
        ]);
    }

    private function html(): string
    {
        $logo = BrandedEmailDesign::logoUrl();
        $address = e((string) MailSettings::footerAddress());

        return <<<HTML
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#F4F4F4;font-family:Arial,Helvetica,sans-serif;color:#393939">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td align="center" style="padding:24px 12px">
<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:100%;background:#FFFFFF;border-radius:16px;overflow:hidden">
<tr><td align="center" style="padding:28px 32px 8px"><img src="{$logo}" width="150" alt="Quiebre" style="display:inline-block;border:0;height:auto"></td></tr>
<tr><td style="padding:16px 32px 0"><h1 style="margin:0;font-size:26px;line-height:1.25;color:#393939">Hola {{ first_name | default:"" }}, te compartimos tu propuesta</h1></td></tr>
<tr><td style="padding:12px 32px 0;font-size:16px;line-height:1.6">{{ message }}</td></tr>
<tr><td style="padding:16px 32px 0;font-size:15px;line-height:1.6;color:#707070"><strong style="color:#393939">{{ proposal_title }}</strong><br>N.º {{ proposal_number }}{{#if valid_until}} · válida hasta {{ valid_until }}{{/if}}</td></tr>
<tr><td align="center" style="padding:24px 32px 8px"><a href="{{ proposal_url }}" style="display:inline-block;background:#FF5300;color:#FFFFFF;text-decoration:none;font-weight:bold;font-size:16px;padding:16px 32px;border-radius:999px">Ver propuesta</a></td></tr>
<tr><td style="padding:8px 32px 28px;font-size:13px;line-height:1.5;color:#8A8A8A;text-align:center">Desde el enlace puedes revisarla, descargarla en PDF y aceptarla en línea.</td></tr>
<tr><td align="center" style="padding:20px 32px;background:#FAFAFA;font-size:12px;line-height:1.5;color:#8A8A8A">Quiebre · Inteligencia inmobiliaria · <a href="https://www.quiebre.cl" style="color:#FF5300;text-decoration:none">quiebre.cl</a><br>{$address}</td></tr>
</table></td></tr></table></body></html>
HTML;
    }
}
