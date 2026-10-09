// Genera las plantillas por defecto del CRM con el MISMO modelo de bloques y compilador del editor visual
// (resources/js/lib/emailBuilder.ts), para que se puedan abrir y editar en Plantillas → editor visual.
// Uso: node scripts/build-email-designs.mjs   → escribe resources/email-designs/*.json
import { createJiti } from 'jiti';
import { writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const jiti = createJiti(import.meta.url, { alias: { '@': resolve(root, 'resources/js') } });
const { BLOCKS, defaultSettings, compileDesign } = await jiti.import(resolve(root, 'resources/js/lib/emailBuilder.ts'));

const LOGO = '{{LOGO_URL}}';
let n = 0;
const block = (type, props = {}) => ({ id: `b${(++n).toString(36).padStart(7, '0')}`, type, props: { ...BLOCKS[type].props(), ...props } });
const header = () => block('header', { logoUrl: LOGO, logoWidth: 150, padY: 28 });
const heading = (text) => block('heading', { text, size: 26, color: '#393939', padY: 12 });
const text = (t, o = {}) => block('text', { text: t, ...o });
const button = (label, href) => block('button', { label, href, bg: '#64D989', color: '#0B1220', radius: 999, align: 'center', padY: 20 });
const note = (t) => block('text', { text: t, size: 13, color: '#8A8A8A', align: 'center', padY: 6 });
const footer = (transactional) =>
    block('footer', {
        company: '{{ company_name }}',
        address: '{{ADDRESS}}',
        legal: transactional ? 'Recibes este correo porque tienes una cuenta o solicitud en ECORTESCL.' : 'Recibes este correo porque te suscribiste al boletín de ECORTESCL.',
        showUnsubscribe: !transactional,
        showView: false,
    });
const settings = { ...defaultSettings(), bg: '#F4F4F4', contentBg: '#FFFFFF', width: 600, font: 'Arial, Helvetica, sans-serif', textColor: '#393939', linkColor: '#15803D', radius: 16 };

const templates = {
    'bienvenida-usuario': {
        name: 'Bienvenida de usuario',
        description: 'Se envía al crear un usuario del CRM, con su correo, contraseña inicial y enlace de acceso.',
        category: 'transactional',
        subject: 'Tu acceso al CRM de ECORTESCL',
        preheader: 'Aquí tienes tus datos para ingresar',
        variables: [
            ['first_name', 'Nombre', '', 'María'],
            ['email', 'Correo de acceso', '', 'maria@ecortes.cl'],
            ['password', 'Contraseña inicial', '', 'Qb-Ejemplo-123'],
            ['role', 'Rol', '', 'Comercial'],
            ['login_url', 'Enlace de acceso', 'https://crm.ecortes.cl/login', 'https://crm.ecortes.cl/login'],
        ],
        blocks: [
            header(),
            heading('¡Bienvenido/a, {{ first_name }}!'),
            text('Te creamos un acceso al CRM de ECORTESCL{{#if role}} con el rol **{{ role }}**{{/if}}. Estos son tus datos para ingresar:'),
            text('Usuario (correo)\n**{{ email }}**\n\nContraseña inicial\n**{{ password }}**', { bg: '#FAFAFA', padY: 16, size: 16 }),
            button('Ingresar al CRM', '{{ login_url }}'),
            note('Por seguridad, cambia tu contraseña después de ingresar (Configuración → Seguridad). Si no esperabas este correo, ignóralo.'),
            footer(true),
        ],
    },
    'recuperar-password': {
        name: 'Recuperar contraseña',
        description: 'Enlace para restablecer la contraseña (flujo «¿Olvidaste tu contraseña?»).',
        category: 'transactional',
        subject: 'Restablece tu contraseña del CRM de ECORTESCL',
        preheader: 'Usa este enlace para crear una nueva contraseña',
        variables: [
            ['first_name', 'Nombre', '', 'María'],
            ['reset_url', 'Enlace para restablecer', '', 'https://crm.ecortes.cl/reset-password/token'],
            ['expires_minutes', 'Minutos de vigencia', '60', '60'],
        ],
        blocks: [
            header(),
            heading('Restablece tu contraseña'),
            text('Hola {{ first_name }}, recibimos una solicitud para restablecer la contraseña de tu acceso al CRM de ECORTESCL.'),
            button('Crear nueva contraseña', '{{ reset_url }}'),
            note('El enlace vence en {{ expires_minutes }} minutos y solo se puede usar una vez.'),
            note('Si no lo solicitaste, ignora este correo: tu contraseña actual sigue funcionando.'),
            footer(true),
        ],
    },
    'gracias-contacto': {
        name: 'Gracias por contactarnos',
        description: 'Respuesta automática al formulario de contacto de ecortes.cl.',
        category: 'transactional',
        subject: 'Recibimos tu mensaje, {{ first_name }}',
        preheader: 'Te responderemos a la brevedad',
        variables: [
            ['first_name', 'Nombre', '', 'María'],
            ['site_url', 'Sitio web', 'https://www.ecortes.cl', 'https://www.ecortes.cl'],
        ],
        blocks: [
            header(),
            heading('¡Gracias por escribirnos, {{ first_name }}!'),
            text('Recibimos tu mensaje y ya lo está revisando nuestro equipo. **Te responderemos a la brevedad** para conversar sobre tu proyecto.'),
            block('heading', { text: 'Cómo trabajamos', size: 18, color: '#393939', padY: 8 }),
            text('**1. Conversamos.** Entendemos tu problema, tus plazos y lo que necesitas lograr.\n\n**2. Proponemos.** Te enviamos una propuesta clara, con alcance, plazos e inversión en UF.\n\n**3. Construimos.** Desarrollamos por iteraciones, con demos periódicas, hasta dejar todo en producción.'),
            button('Ver nuestros servicios', '{{ site_url }}'),
            note('Software Factory con más de 10 años de experiencia: desarrollo web, apps móviles, automatizaciones e inteligencia artificial.'),
            footer(true),
        ],
    },
    'gracias-cotizacion': {
        name: 'Recibimos tu solicitud de cotización',
        description: 'Confirmación al solicitar una cotización o reunión: pronto se confirmará y contactará al solicitante.',
        category: 'transactional',
        subject: 'Recibimos tu solicitud de cotización',
        preheader: 'Pronto te confirmaremos y contactaremos',
        variables: [
            ['first_name', 'Nombre', '', 'María'],
            ['site_url', 'Sitio web', 'https://www.ecortes.cl', 'https://www.ecortes.cl'],
            ['tipo_servicio', 'Servicio de interés', '', 'Sitio web'],
            ['fecha_preferida', 'Fecha preferida', '', '15-10-2026'],
        ],
        blocks: [
            header(),
            heading('Gracias por tu solicitud, {{ first_name }}'),
            text('Recibimos tu solicitud de cotización. **Pronto te estaremos confirmando** y contactando para acelerar el proceso.'),
            text('{{#if tipo_servicio}}Servicio de interés: **{{ tipo_servicio }}**{{/if}}{{#if fecha_preferida}}\nReunión preferida: **{{ fecha_preferida }}**{{/if}}', { color: '#707070', size: 15 }),
            block('heading', { text: 'Qué sigue', size: 18, color: '#393939', padY: 8 }),
            text('**1.** Un ejecutivo de ECORTESCL revisa tu solicitud.\n\n**2.** Te escribimos o llamamos para confirmar la reunión.\n\n**3.** Preparamos una propuesta a la medida de tu proyecto.'),
            button('Conoce ECORTESCL', '{{ site_url }}'),
            footer(true),
        ],
    },
    'propuesta-respondida': {
        name: 'Propuesta respondida por el cliente',
        description: 'Aviso interno al responsable cuando el cliente firma, solicita ajustes o rechaza una propuesta.',
        category: 'transactional',
        subject: '{{ action }}: {{ proposal_number }} · {{ client_name }}',
        preheader: '{{ responder }} respondió la propuesta',
        variables: [
            ['first_name', 'Responsable', '', 'Esteban'],
            ['action', 'Respuesta', '', 'Propuesta firmada y aceptada'],
            ['client_name', 'Cliente', '', 'Oritec SpA'],
            ['responder', 'Quién respondió', '', 'Cristian Suarez'],
            ['proposal_title', 'Título', '', 'Especialista en IA'],
            ['proposal_number', 'Número', '', 'P-2026-0001'],
            ['note', 'Comentario del cliente', '', 'Necesitamos ajustar las fechas.'],
            ['proposal_url', 'Enlace interno', '', 'https://crm.ecortes.cl/proposals/1'],
        ],
        blocks: [
            header(),
            heading('{{ action }}'),
            text('Hola {{ first_name }}, **{{ responder }}** ({{ client_name }}) respondió la propuesta **{{ proposal_number }} · {{ proposal_title }}**.'),
            text('{{#if note}}Comentario del cliente:\n\n«{{ note }}»{{/if}}', { color: '#707070', size: 15 }),
            button('Ver propuesta en el CRM', '{{ proposal_url }}'),
            footer(true),
        ],
    },
    'propuesta-comercial': {
        name: 'Propuesta comercial',
        description: 'Correo con el enlace a la propuesta comercial (se usa al enviar desde Propuestas).',
        category: 'transactional',
        subject: 'Propuesta comercial: {{ proposal_title }}',
        preheader: 'Revisa tu propuesta de ECORTESCL',
        variables: [
            ['first_name', 'Nombre', '', 'María'],
            ['proposal_title', 'Título de la propuesta', '', 'Marketing digital Proyecto Torre Norte'],
            ['proposal_number', 'Número', '', 'P-2026-0001'],
            ['proposal_url', 'Enlace', '', 'https://www.ecortes.cl'],
            ['valid_until', 'Válida hasta', '', '30 de octubre de 2026'],
            ['message', 'Mensaje', '', 'Adjunto la propuesta que conversamos.'],
        ],
        blocks: [
            header(),
            heading('Hola {{ first_name }}, te compartimos tu propuesta'),
            text('{{ message }}'),
            text('**{{ proposal_title }}**\nN.º {{ proposal_number }}{{#if valid_until}} · válida hasta {{ valid_until }}{{/if}}', { color: '#707070', size: 15 }),
            button('Ver propuesta', '{{ proposal_url }}'),
            note('Desde el enlace puedes revisarla, descargarla en PDF y aceptarla en línea.'),
            footer(true),
        ],
    },
};

for (const [slug, t] of Object.entries(templates)) {
    const design = { settings, blocks: t.blocks };
    const out = {
        slug,
        name: t.name,
        description: t.description,
        category: t.category,
        subject: t.subject,
        preheader: t.preheader,
        variables: t.variables.map(([key, label, def, sample]) => ({ key, label, default: def, sample })),
        design,
        html: compileDesign(design),
    };
    writeFileSync(resolve(root, `resources/email-designs/${slug}.json`), JSON.stringify(out, null, 2) + '\n');
    console.log('✓', slug, `${out.html.length} bytes`);
}
