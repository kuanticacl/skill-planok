<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /** Catálogo inicial de ECORTESCL (tarifas netas en UF; editables en Servicios). */
    public function run(): void
    {
        if (Service::count() > 0) {
            return;
        }

        $catalog = [
            ['Desarrollo web', 'Landing page corporativa', 'Página de aterrizaje rápida y optimizada para conversión, conectada a formularios, analítica y a tu CRM.', ['Diseño responsive con tu identidad', 'Formulario conectado al CRM', 'SEO técnico y analítica', 'Publicación y capacitación'], 'one_time', 'proyecto', 18],
            ['Desarrollo web', 'Sitio web corporativo', 'Sitio institucional administrable, con varias secciones, blog y SEO técnico.', ['Diseño UX/UI', 'Panel de administración de contenidos', 'Optimización de velocidad y SEO', 'Hosting inicial configurado'], 'one_time', 'proyecto', 45],
            ['Desarrollo web', 'Tienda online (e-commerce)', 'Tienda con catálogo, carro de compras, pagos en línea y gestión de pedidos.', ['Catálogo y fichas de producto', 'Pasarela de pago (Webpay, Mercado Pago)', 'Gestión de pedidos e inventario', 'Capacitación'], 'one_time', 'proyecto', 90],
            ['Desarrollo web', 'Plataforma web a medida', 'Aplicación web a la medida de tus procesos (Laravel, Vue o Nuxt) con usuarios, roles y reportes.', ['Levantamiento y diseño de la solución', 'Desarrollo por iteraciones con demos', 'Usuarios, roles y permisos', 'Puesta en producción y documentación'], 'one_time', 'proyecto', 180],
            ['Aplicaciones móviles', 'App móvil iOS y Android', 'Aplicación móvil multiplataforma conectada a tu backend, con notificaciones y publicación en tiendas.', ['Diseño de la experiencia', 'Desarrollo iOS y Android', 'Notificaciones push', 'Publicación en App Store y Google Play'], 'one_time', 'proyecto', 220],
            ['Automatización e integraciones', 'Automatización de procesos', 'Eliminamos tareas manuales y repetitivas conectando tus herramientas con flujos automáticos.', ['Mapeo del proceso actual', 'Flujos automatizados y alertas', 'Pruebas y monitoreo', 'Documentación'], 'one_time', 'proyecto', 35],
            ['Automatización e integraciones', 'Integración de sistemas y APIs', 'Conectamos tu ERP, CRM, pasarelas de pago y otros sistemas mediante APIs seguras.', ['Análisis de las APIs involucradas', 'Desarrollo de la integración', 'Manejo de errores y reintentos', 'Documentación técnica'], 'one_time', 'proyecto', 40],
            ['Automatización e integraciones', 'Dashboard y reportería', 'Tablero con tus indicadores clave en tiempo real, conectado a tus fuentes de datos.', ['Conexión de fuentes de datos', 'Dashboard personalizado', 'Reportes programados por correo'], 'one_time', 'proyecto', 30],
            ['Inteligencia artificial', 'Agente de IA y chatbot WhatsApp', 'Asistente conversacional que atiende consultas, califica prospectos y deriva a una persona cuando corresponde.', ['Diseño del flujo y la personalidad', 'Entrenamiento con tu información', 'Integración con WhatsApp y CRM', 'Métricas y mejora continua'], 'one_time', 'proyecto', 45],
            ['Inteligencia artificial', 'Asistente interno con IA', 'Herramienta con IA para tu equipo: búsqueda en documentos, redacción y análisis de información propia.', ['Levantamiento de casos de uso', 'Implementación segura con tus datos', 'Capacitación al equipo'], 'one_time', 'proyecto', 60],
            ['Mantención y soporte', 'Mantención web mensual', 'Actualizaciones, respaldos, monitoreo y soporte para mantener tu sitio seguro y funcionando.', ['Actualizaciones de seguridad', 'Respaldos y monitoreo', 'Soporte por correo', 'Reporte mensual'], 'monthly', 'mes', 4],
            ['Mantención y soporte', 'Soporte y evolución de plataforma', 'Equipo disponible para corregir, mejorar y evolucionar tu plataforma de forma continua.', ['Bolsa mensual de horas', 'Corrección de errores', 'Nuevas funcionalidades priorizadas', 'Reunión mensual de avance'], 'monthly', 'mes', 12],
            ['Mantención y soporte', 'Hora de desarrollo', 'Horas de desarrollo o consultoría técnica para requerimientos puntuales.', ['Estimación previa', 'Desarrollo o asesoría', 'Entrega con detalle de horas'], 'one_time', 'hora', 1.2],
            ['Growth marketing', 'Estrategia de growth y embudo de ventas', 'Diagnóstico y diseño del embudo de captación: canales, mensajes, oferta y metas, con un plan de crecimiento a 90 días.', ['Diagnóstico de situación y competencia', 'Diseño del embudo y definición de KPIs', 'Plan de acción a 90 días', 'Reunión de presentación'], 'one_time', 'proyecto', 30],
            ['Growth marketing', 'Gestión de campañas Meta Ads', 'Campañas de generación de clientes en Facebook e Instagram: estrategia, creatividades, segmentación y optimización semanal.', ['Estrategia y segmentación', 'Creatividades y copys', 'Optimización semanal', 'Reporte mensual de resultados'], 'monthly', 'mes', 25],
            ['Growth marketing', 'Gestión de campañas Google Ads', 'Campañas de búsqueda y display para captar clientes con intención activa de compra.', ['Investigación de palabras clave', 'Campañas de búsqueda y display', 'Seguimiento de conversiones', 'Reporte mensual'], 'monthly', 'mes', 25],
            ['Growth marketing', 'SEO y contenidos', 'Posicionamiento orgánico: auditoría técnica, palabras clave y contenidos que atraen tráfico calificado.', ['Auditoría SEO técnica', 'Plan de palabras clave y contenidos', 'Publicación de contenidos mensuales', 'Reporte de posicionamiento'], 'monthly', 'mes', 18],
            ['Growth marketing', 'Email marketing y marketing automation', 'Secuencias y boletines automatizados para nutrir prospectos y reactivar clientes, conectados a tu CRM.', ['Diseño de plantillas', 'Secuencias automatizadas', 'Segmentación de audiencias', 'Reporte de aperturas y conversiones'], 'monthly', 'mes', 15],
            ['Growth marketing', 'Optimización de conversión (CRO)', 'Mejoras continuas en tu sitio o landing basadas en datos: pruebas A/B, experiencia de usuario y formularios.', ['Análisis de comportamiento', 'Hipótesis y pruebas A/B', 'Implementación de mejoras', 'Reporte de resultados'], 'monthly', 'mes', 14],
            ['Growth marketing', 'Analítica y tracking (GA4, GTM, píxeles)', 'Medición confiable de campañas y conversiones, conectada a dashboards y a tu CRM.', ['Configuración de GA4 y Tag Manager', 'Píxeles y eventos de conversión', 'Dashboard de resultados'], 'one_time', 'proyecto', 15],
        ];

        foreach ($catalog as $i => [$category, $name, $description, $deliverables, $billing, $unit, $price]) {
            Service::create(compact('category', 'name', 'description', 'deliverables', 'billing', 'unit', 'price') + ['currency' => 'UF', 'sort_order' => $i]);
        }
    }
}
