<?php

namespace App\Services\Email;

use App\Models\ContactList;
use App\Models\EmailTemplate;
use App\Models\LeadField;
use App\Models\LeadSource;
use App\Services\Ai\BrandedEmailDesign;

/**
 * Recursos del CRM para la landing top-inmobiliario.quiebre.cl: orígenes (cada uno con su API key),
 * audiencia del boletín, campos personalizados y las dos plantillas de agradecimiento.
 * Es idempotente: solo crea lo que falta y nunca pisa lo que ya se editó en el CRM.
 */
class LandingKit
{
    public const SITE = 'https://top-inmobiliario.quiebre.cl';

    public const NEWSLETTER_TEMPLATE = 'bienvenida-top-inmobiliario';

    public const ADVISORY_TEMPLATE = 'confirmacion-asesoria';

    public const AUDIENCE = 'Boletín Top Inmobiliario';

    /** @return array<string, string> resumen de lo creado */
    public function install(): array
    {
        $created = [];

        foreach ([['Landing Top Inmobiliario – Boletín', '#FF5300', 'newspaper', 20], ['Landing Top Inmobiliario – Asesoría', '#6419DB', 'calendar-check', 21]] as [$name, $color, $icon, $order]) {
            $slug = \Illuminate\Support\Str::slug($name);
            if (! LeadSource::where('slug', $slug)->exists()) {
                LeadSource::create(['name' => $name, 'slug' => $slug, 'color' => $color, 'icon' => $icon, 'api_key' => LeadSource::generateKey(), 'sort_order' => $order]);
                $created[] = "origen: $name";
            }
        }

        if (! ContactList::where('name', self::AUDIENCE)->exists()) {
            ContactList::create(['name' => self::AUDIENCE, 'description' => 'Suscriptores del boletín semanal del ranking Top Inmobiliarias de Chile.']);
            $created[] = 'audiencia: '.self::AUDIENCE;
        }

        $fields = [
            ['tipo_solicitud', 'Tipo de solicitud', 'text'],
            ['fecha_preferida', 'Fecha preferida de la asesoría', 'date'],
            ['horario_preferido', 'Horario preferido', 'select', ['mañana', 'tarde']],
            ['etapa_proyecto', 'Etapa del proyecto', 'text'],
            ['interes', 'Interés', 'text'],
        ];
        foreach ($fields as $i => $f) {
            [$key, $label, $type] = $f;
            $options = $f[3] ?? null;
            if (! LeadField::where('key', $key)->exists()) {
                LeadField::create(['key' => $key, 'label' => $label, 'type' => $type, 'options' => $options, 'sort_order' => 10 + $i]);
                $created[] = "campo: $label";
            }
        }

        if (! EmailTemplate::where('slug', self::NEWSLETTER_TEMPLATE)->exists()) {
            $this->newsletter();
            $created[] = 'plantilla: Bienvenida Top Inmobiliario';
        }
        if (! EmailTemplate::where('slug', self::ADVISORY_TEMPLATE)->exists()) {
            $this->advisory();
            $created[] = 'plantilla: Confirmación de asesoría';
        }

        // Logo negro original en las plantillas por defecto que se crearon con el logo naranja.
        foreach ([self::NEWSLETTER_TEMPLATE, self::ADVISORY_TEMPLATE, \App\Services\Proposals\ProposalMailer::SLUG] as $slug) {
            $t = EmailTemplate::where('slug', $slug)->first();
            if ($t && str_contains((string) $t->html, '/brand/quiebre-logo.png')) {
                $t->update(['html' => str_replace('/brand/quiebre-logo.png', '/brand/quiebre-logo-dark.png', $t->html)]);
                $created[] = "logo negro: $slug";
            }
        }

        return $created;
    }

    private function newsletter(): void
    {
        $body = <<<'HTML'
<tr><td style="padding:16px 32px 0"><h1 style="margin:0;font-size:26px;line-height:1.25;color:#393939">¡Gracias por suscribirte{{#if first_name}}, {{ first_name }}{{/if}}!</h1></td></tr>
<tr><td style="padding:12px 32px 0;font-size:16px;line-height:1.6">Ya eres parte del boletín de <strong>Top Inmobiliarias de Chile</strong>, el ranking digital de QUIEBRE que mide el desempeño de 50 inmobiliarias del país. Cada semana te contamos qué cambió y qué está pasando en el mercado.</td></tr>
<tr><td style="padding:20px 32px 0;font-size:18px;font-weight:bold;color:#393939">Cómo funciona el ranking</td></tr>
<tr><td style="padding:8px 32px 0;font-size:15px;line-height:1.6">
<p style="margin:0 0 10px"><strong style="color:#FF5300">1. Evaluamos cinco áreas.</strong> Web (35 pts), Tecnología (25), Redes sociales (15), Visibilidad digital (15) y Branding (15). El puntaje se normaliza a una nota de 1 a 100.</p>
<p style="margin:0 0 10px"><strong style="color:#FF5300">2. Cada inmobiliaria tiene su ficha.</strong> Ahí ves la nota por criterio, la evidencia pública que la respalda y qué le falta para subir.</p>
<p style="margin:0 0 10px"><strong style="color:#FF5300">3. Se actualiza cada semana.</strong> Los cambios de posición y los hallazgos llegan primero a tu correo.</p>
<p style="margin:0;color:#707070;font-size:13px">El ranking mide desempeño digital; no evalúa calidad constructiva, satisfacción de clientes ni solvencia financiera.</p></td></tr>
<tr><td align="center" style="padding:24px 32px 8px"><a href="{{ site_url }}" style="display:inline-block;background:#FF5300;color:#FFFFFF;text-decoration:none;font-weight:bold;font-size:16px;padding:16px 32px;border-radius:999px">Ver el ranking</a></td></tr>
<tr><td style="padding:8px 32px 28px;font-size:14px;line-height:1.6;color:#707070;text-align:center">¿Quieres saber cómo subir en el ranking? En la web puedes agendar una asesoría con nuestro equipo.</td></tr>
HTML;

        $this->make(self::NEWSLETTER_TEMPLATE, 'Bienvenida Top Inmobiliario', 'Agradecimiento a quienes se suscriben al boletín, con una explicación de cómo funciona el ranking.',
            '¡Gracias por suscribirte al ranking Top Inmobiliarias!', 'Así funciona el ranking y qué recibirás cada semana', $body, 'marketing');
    }

    private function advisory(): void
    {
        $body = <<<'HTML'
<tr><td style="padding:16px 32px 0"><h1 style="margin:0;font-size:26px;line-height:1.25;color:#393939">Gracias por agendar{{#if first_name}}, {{ first_name }}{{/if}}</h1></td></tr>
<tr><td style="padding:12px 32px 0;font-size:16px;line-height:1.6">Recibimos tu solicitud de asesoría. <strong>Pronto te estaremos confirmando</strong> el horario y contactándote para acelerar tu solicitud.</td></tr>
{{#if fecha_preferida}}<tr><td style="padding:16px 32px 0;font-size:15px;line-height:1.6;color:#707070">Tu preferencia: <strong style="color:#393939">{{ fecha_preferida }}{{#if horario_preferido}} · {{ horario_preferido }}{{/if}}</strong></td></tr>{{/if}}
<tr><td style="padding:20px 32px 0;font-size:18px;font-weight:bold;color:#393939">Qué sigue</td></tr>
<tr><td style="padding:8px 32px 0;font-size:15px;line-height:1.6">
<p style="margin:0 0 10px"><strong style="color:#FF5300">1.</strong> Un ejecutivo de QUIEBRE revisa tu solicitud.</p>
<p style="margin:0 0 10px"><strong style="color:#FF5300">2.</strong> Te escribimos o llamamos para confirmar día y hora.</p>
<p style="margin:0"><strong style="color:#FF5300">3.</strong> En la asesoría revisamos el desempeño digital de tu inmobiliaria y las oportunidades para mejorar.</p></td></tr>
<tr><td align="center" style="padding:24px 32px 28px"><a href="{{ site_url }}" style="display:inline-block;background:#FF5300;color:#FFFFFF;text-decoration:none;font-weight:bold;font-size:16px;padding:16px 32px;border-radius:999px">Volver al ranking</a></td></tr>
HTML;

        $this->make(self::ADVISORY_TEMPLATE, 'Confirmación de asesoría', 'Agradecimiento al agendar una asesoría: pronto se confirmará y contactará al solicitante.',
            'Recibimos tu solicitud de asesoría', 'Pronto te confirmaremos y contactaremos', $body, 'transactional');
    }

    private function make(string $slug, string $name, string $description, string $subject, string $preheader, string $body, string $category): void
    {
        $logo = BrandedEmailDesign::logoUrl();
        $address = e((string) MailSettings::footerAddress());

        $html = <<<HTML
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#F4F4F4;font-family:Arial,Helvetica,sans-serif;color:#393939">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td align="center" style="padding:24px 12px">
<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:100%;background:#FFFFFF;border-radius:16px;overflow:hidden">
<tr><td align="center" style="padding:28px 32px 8px"><img src="{$logo}" width="150" alt="Quiebre" style="display:inline-block;border:0;height:auto"></td></tr>
{$body}
<tr><td align="center" style="padding:20px 32px;background:#FAFAFA;font-size:12px;line-height:1.5;color:#8A8A8A">Quiebre · Inteligencia inmobiliaria · <a href="https://www.quiebre.cl" style="color:#FF5300;text-decoration:none">quiebre.cl</a><br>{$address}</td></tr>
</table></td></tr></table></body></html>
HTML;

        EmailTemplate::create([
            'slug' => $slug, 'name' => $name, 'description' => $description, 'category' => $category,
            'subject' => $subject, 'preheader' => $preheader, 'editor' => 'html', 'html' => $html, 'is_active' => true,
            'variables' => [
                ['key' => 'first_name', 'label' => 'Nombre', 'default' => '', 'sample' => 'María'],
                ['key' => 'site_url', 'label' => 'Enlace a la landing', 'default' => self::SITE, 'sample' => self::SITE],
                ['key' => 'fecha_preferida', 'label' => 'Fecha preferida', 'default' => '', 'sample' => '15-10-2026'],
                ['key' => 'horario_preferido', 'label' => 'Horario preferido', 'default' => '', 'sample' => 'tarde'],
            ],
        ]);
    }
}
