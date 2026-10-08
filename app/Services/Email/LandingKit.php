<?php

namespace App\Services\Email;

use App\Models\ContactList;
use App\Models\EmailTemplate;
use App\Models\LeadField;
use App\Models\LeadSource;

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

        $templates = app(DefaultTemplates::class);
        foreach ([self::NEWSLETTER_TEMPLATE => 'Bienvenida Top Inmobiliario', self::ADVISORY_TEMPLATE => 'Confirmación de asesoría', UserMailer::WELCOME => 'Bienvenida de usuario', UserMailer::RESET => 'Recuperar contraseña', \App\Services\Proposals\ProposalMailer::SLUG => 'Propuesta comercial'] as $slug => $label) {
            $before = EmailTemplate::where('slug', $slug)->first();
            $templates->ensure($slug);
            if (! $before || $before->editor !== 'blocks') {
                $created[] = ($before ? 'plantilla pasada al editor visual: ' : 'plantilla: ').$label;
            }
        }

        return $created;
    }
}
