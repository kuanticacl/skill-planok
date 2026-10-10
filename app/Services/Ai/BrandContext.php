<?php

namespace App\Services\Ai;

use App\Models\Setting;

/** Contexto de marca de ECORTESCL que se entrega a la IA (voz, propuesta de valor y reglas de estilo). */
class BrandContext
{
    public static function defaultText(): string
    {
        return <<<'TXT'
ECORTESCL es una Software Factory y agencia de Growth Marketing chilena (Providencia, Santiago) con más de 10 años de experiencia transformando empresas con tecnología y marketing orientado a resultados. Posicionamiento: soluciones tecnológicas a medida y estrategias de crecimiento que ahorran tiempo, generan clientes y hacen crecer el negocio.
Servicios de software: desarrollo web profesional (sitios, plataformas, e-commerce), aplicaciones móviles, automatizaciones e integraciones entre sistemas, inteligencia artificial aplicada (agentes, chatbots, análisis) y mantención y soporte. Stack: Laravel, Node.js, Vue, Nuxt, WordPress y MySQL.
Servicios de growth marketing: estrategia de crecimiento y embudos de venta, campañas de performance (Meta Ads, Google Ads), SEO y contenidos, email marketing y marketing automation, optimización de conversión (CRO) y analítica/medición (GA4, GTM, píxeles) conectada al CRM.
Voz: profesional, cercana, técnica pero clara. Español de Chile, trato de «tú», sin jerga vacía. Frases cortas, beneficios concretos (tiempo ahorrado, procesos más simples, resultados medibles) y plazos realistas. Evita promesas exageradas, mayúsculas gritadas y signos de exclamación en exceso.
Llamados a la acción típicos: «Cuéntanos tu proyecto», «Agenda una reunión», «Conversemos».
TXT;
    }

    public static function text(): string
    {
        return Setting::get('ai.brand_context') ?: self::defaultText();
    }
}
