<?php

namespace App\Services\Ai;

use App\Models\Setting;

/** Contexto de marca de Quiebre que se entrega a la IA (voz, propuesta de valor y reglas de estilo). */
class BrandContext
{
    public static function defaultText(): string
    {
        return <<<'TXT'
Quiebre es una agencia de marketing inmobiliario en Chile con más de 10 años de experiencia. Posicionamiento: «Inteligencia inmobiliaria — Deja de adivinar y empieza a convertir».
Ayuda a inmobiliarias, constructoras y corredoras a atraer y cerrar más ventas con datos y gestión comercial: marketing y Ads, redes sociales, branding, reporting y big data inmobiliario, aceleración de ventas e IA activa. Tiene su propia plataforma de gestión de leads (integraleads).
Voz: profesional, cercana, directa y orientada a resultados. Español de Chile, trato de «tú», sin modismos excesivos ni jerga vacía. Frases cortas, beneficios concretos y números cuando existan. Evita promesas exageradas, mayúsculas gritadas y signos de exclamación en exceso.
Llamados a la acción típicos: «Agenda una demo», «Conversemos», «Ver cómo lo hacemos».
TXT;
    }

    public static function text(): string
    {
        return Setting::get('ai.brand_context') ?: self::defaultText();
    }
}
