<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /** Catálogo inicial de la agencia (tarifas referenciales en CLP neto; editables en Servicios). */
    public function run(): void
    {
        if (Service::count() > 0) {
            return;
        }

        $catalog = [
            ['Marketing digital', 'Gestión de campañas Meta Ads', 'Campañas de generación de leads en Facebook e Instagram: estrategia, creatividades, segmentación y optimización semanal.', ['Estrategia y segmentación', 'Hasta 6 creatividades al mes', 'Optimización semanal', 'Informe mensual de resultados'], 'monthly', 'mes', 650000],
            ['Marketing digital', 'Gestión de campañas Google Ads', 'Campañas de búsqueda y display para captar compradores con intención activa.', ['Investigación de palabras clave', 'Campañas de búsqueda y display', 'Seguimiento de conversiones', 'Informe mensual'], 'monthly', 'mes', 600000],
            ['Marketing digital', 'Landing page de proyecto', 'Página de aterrizaje optimizada para conversión, conectada al CRM y a la medición de campañas.', ['Diseño con identidad del proyecto', 'Formulario conectado al CRM', 'Píxeles y analítica', 'Versión móvil'], 'one_time', 'proyecto', 890000],
            ['Redes sociales', 'Community management', 'Planificación, creación y publicación de contenido, y gestión de la comunidad.', ['Calendario editorial', '12 publicaciones al mes', 'Respuesta a mensajes y comentarios', 'Reporte mensual'], 'monthly', 'mes', 480000],
            ['Redes sociales', 'Producción de contenido audiovisual', 'Fotografía y video para proyectos inmobiliarios (reels, recorridos y piezas de venta).', ['Jornada de producción', 'Edición de video y reels', 'Fotografía de proyecto'], 'one_time', 'jornada', 750000],
            ['Branding', 'Identidad de marca de proyecto', 'Naming, logotipo y lineamientos gráficos del proyecto inmobiliario.', ['Concepto y logotipo', 'Paleta y tipografías', 'Manual de marca resumido'], 'one_time', 'proyecto', 1200000],
            ['Datos e inteligencia', 'Reporting y dashboard comercial', 'Tablero de resultados con inversión, leads, costo por lead y avance de ventas.', ['Conexión de fuentes de datos', 'Dashboard personalizado', 'Reunión mensual de resultados'], 'monthly', 'mes', 350000],
            ['Datos e inteligencia', 'Big data inmobiliario', 'Análisis de mercado, oferta y demanda de la zona para posicionar el proyecto.', ['Estudio de competencia', 'Análisis de precios y absorción', 'Recomendaciones comerciales'], 'one_time', 'estudio', 950000],
            ['Gestión comercial', 'Aceleración de ventas', 'Acompañamiento a la sala de ventas y al equipo comercial con seguimiento de leads en integraleads.', ['Diagnóstico del proceso comercial', 'Configuración de embudo y automatizaciones', 'Seguimiento semanal'], 'monthly', 'mes', 800000],
            ['Gestión comercial', 'Implementación de integraleads', 'Puesta en marcha de la plataforma de gestión de leads: configuración, integraciones y capacitación.', ['Configuración inicial', 'Integración con campañas y landing', 'Capacitación al equipo'], 'one_time', 'proyecto', 600000],
        ];

        foreach ($catalog as $i => [$category, $name, $description, $deliverables, $billing, $unit, $price]) {
            Service::create(compact('category', 'name', 'description', 'deliverables', 'billing', 'unit', 'price') + ['sort_order' => $i]);
        }
    }
}
