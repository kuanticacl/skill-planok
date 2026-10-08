# Quiebre CRM

CRM interno de **Quiebre** ([quiebre.cl](https://www.quiebre.cl)) para gestionar prospectos de inmobiliarias, con
Kanban comercial y un módulo completo de **email marketing sobre Resend**.
Laravel 13 · Inertia 3 · Vue 3 (TypeScript) · Tailwind 4 · shadcn-vue · SQLite (configurable).

Identidad visual tomada de quiebre.cl: fuente **Asap**, naranja `#FF5300`, wordmark oficial, botones tipo píldora.

## Contenido

- [Qué incluye](#qué-incluye) · [Puesta en marcha](#puesta-en-marcha) · [Roles y permisos](#roles-y-permisos)
- [API de ingreso de leads](#api-de-ingreso-de-leads)
- [Email marketing](#email-marketing): [Resend](#1-conectar-resend) · [Plantillas](#2-plantillas-de-email) · [Boletines](#3-boletines-y-audiencias) · [API de envío](#4-api-de-envío) · [Automatizaciones](#5-automatizaciones) · [Seguimiento y bajas](#6-seguimiento-bajas-y-webhook)
- [Estructura](#estructura) · [Comandos útiles](#comandos-útiles)

## Qué incluye

| Módulo | Descripción |
| --- | --- |
| **Usuarios, roles y permisos** | CRUD de usuarios (activo/inactivo), roles con matriz de permisos aplicada en rutas, policies y vistas. |
| **Dashboard + Kanban** | Recuentos por origen y tablero con *drag & drop*. Tarjetas con prioridad, valor estimado, etiquetas, próximo seguimiento (vencido/hoy), tiempo en etapa y alertas de inactividad; **panel lateral** del lead sin salir del tablero; **selección múltiple** con acciones masivas (mover, asignar, prioridad, etiquetar, eliminar); filtros avanzados (origen, responsable, prioridad, etiqueta, seguimiento vencido…), orden por columna, columnas colapsables, creación rápida por columna, motivo al descartar y valor al concretar, densidad y campos visibles configurables, auto-refresco. |
| **Leads** | Nombre, apellido, correo, teléfono, cargo, empresa + UTM + metadatos de captura (IP, ubicación, navegador, landing, referrer). Notas (con opción **privada**), asignación, historial de seguimiento y campos personalizados. |
| **Orígenes y API** | Cada origen tiene su propia **API key**; el origen del lead se reconoce por la key con que llega. |
| **Etapas del Kanban** | Nombre, color, tipo (en curso / concretado / descartado) y orden configurables. |
| **Clientes** | CRUD con ficha y leads asociados. |
| **Email marketing** | Plantillas con editor visual y HTML, boletines con audiencias y programación, API de envío, automatizaciones, tracking de aperturas/clics, bajas y webhooks de Resend. |

## Puesta en marcha

Requisitos: PHP ≥ 8.3, Composer, Node ≥ 22.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed        # roles, etapas, orígenes y usuario administrador
php artisan storage:link          # para subir imágenes en las plantillas de email
composer run dev                  # servidor + worker de cola + Vite → http://localhost:8000
```

Usuario administrador inicial (configurable con `ADMIN_EMAIL`, `ADMIN_NAME` y `ADMIN_PASSWORD`):
**admin@quiebre.cl** / `password` (solo en entornos no productivos; en producción `ADMIN_PASSWORD` es obligatorio).

Datos de demostración opcionales (usuarios, clientes y 48 leads de ejemplo):

```bash
php artisan db:seed --class=DemoSeeder      # usuarios demo con contraseña "password"
```

> **Producción:** define `APP_URL` con la URL pública (la usan los enlaces de seguimiento, la baja y el webhook),
> mantén un worker (`php artisan queue:work`, p. ej. con Supervisor) y agrega el cron de Laravel
> (`* * * * * php artisan schedule:run`) para los boletines programados. En local: `php artisan schedule:work`.

## Roles y permisos

Los permisos viven en [`config/permissions.php`](config/permissions.php), agrupados (`leads.view`, `campaigns.send`, …).
Agregar uno nuevo basta con sumarlo ahí: aparece solo en la matriz de roles y queda disponible como:

```php
Route::get(...)->middleware('can:clients.create');   // rutas
$user->hasPermission('leads.assign');                // código
```
```ts
const { can } = usePermissions();                    // Vue
can('leads.delete')
```

- El rol **Administrador** (`admin`) siempre tiene todos los permisos y no se puede editar ni eliminar.
- `leads.view` muestra solo los leads **asignados** al usuario; `leads.view_all` muestra todos.
- Permisos de email: `templates.view|manage`, `campaigns.view|create|send`, `lists.manage`, `automations.manage`,
  `email_logs.view`, `email_settings.manage`.

## API de ingreso de leads

`POST /api/v1/leads` · el origen se identifica por la **API key del origen** (*Orígenes y API*).

```bash
curl -X POST https://TU-DOMINIO/api/v1/leads \
  -H "X-Api-Key: qb_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "María", "last_name": "González",
    "email": "maria@ejemplo.cl", "phone": "+56912345678",
    "company": "Inmobiliaria Ejemplo", "job_title": "Gerente comercial",
    "message": "Quiero cotizar una campaña",
    "utm_source": "google", "utm_medium": "cpc", "utm_campaign": "proyecto-otono",
    "landing_url": "https://tusitio.cl/contacto", "referrer": "https://www.google.com/",
    "ip": "200.1.2.3",
    "custom": { "proyecto_de_interes": "Torre Norte" },
    "email_template": "bienvenida"
  }'
```

- Requerido: `first_name` (o `name`) y al menos `email` o `phone`.
- Se aceptan `utm_*`, `ip`, `user_agent`, `referrer`, `landing_url`, `country`, `region`, `city`, `latitude`, `longitude`.
- Campos personalizados dentro de `custom` (o sueltos) con su **clave**. Cualquier otro dato se guarda en los metadatos
  del lead y queda **disponible como variable** en los emails.
- `email_template` (slug) envía además esa plantilla al lead recién creado; `email_variables` agrega variables extra.
- Sin `ip`/`user_agent` se toman de la petición; la ubicación se completa con headers del CDN (`CF-IPCountry`, `X-Vercel-IP-*`…).
  **Si llamas desde el backend de un sitio, envía la `ip` del visitante.**
- Respuestas: `201` · `401` key inválida · `403` origen desactivado · `422` validación · `429` límite (120/min por key).

# Email marketing

Todo vive bajo **Email marketing** en el menú: Boletines, Plantillas, Audiencias, Automatizaciones, Historial de envíos,
Bajas y rebotes, API e integraciones y Configuración.

## 1. Conectar Resend

1. En [resend.com](https://resend.com) crea una **API key** y **verifica tu dominio** de envío.
2. En el CRM: *Email → Configuración* → pega la API key, define remitente (`hola@tudominio.cl`), nombre de empresa y dirección
   postal (pie legal de los boletines). También puedes usar `RESEND_API_KEY` en el `.env`.
3. Pulsa **Enviar prueba** para validar la conexión.

Mientras no haya API key el CRM está en **modo prueba**: arma y "envía" todo pero solo lo registra en el log, sin salir ningún correo.

## 2. Plantillas de email

*Email → Plantillas → Nueva plantilla*. Parte de una base (bienvenida de landing, boletín, seguimiento de cotización, en blanco)
o pega tu propio HTML.

- **Editor visual por bloques**: encabezado, título, texto (`**negrita**`, `*cursiva*`, `[enlace](url)`), imagen (URL o subida), botón,
  imagen + texto, **lista dinámica** (repite una tarjeta por cada elemento recibido: proyectos, propiedades…), divisor,
  espaciador, redes, pie de página y HTML libre. Se arrastran para ordenar y se editan con un panel de propiedades.
  El HTML resultante usa tablas y estilos en línea (compatible con Gmail/Outlook) y se apila en móvil.
- **Editor HTML** con vista previa en vivo, y alternancia Visual ⇄ HTML.
- Vista previa **escritorio/móvil**, clic en el correo para seleccionar el bloque, envío de **prueba** inmediato y botón **API**
  con ejemplos listos (cURL, Node, PHP, query string) usando las variables de esa plantilla.
- Asunto y *preheader* con variables. Atajo `Ctrl/Cmd + S` para guardar.

### Variables y sintaxis

Las variables se detectan solas al escribir `{{ nombre }}`; en la pestaña *Variables* defines un **valor de ejemplo** (vista previa y
pruebas) y un **valor por defecto** (si la API no lo envía).

```
{{ first_name }}                         variable (se escapa para HTML)
{{ lead.company }}                       ruta con puntos
{{ first_name | default:"estimado/a" }}  valor por defecto
{{ monto | money }}  {{ fecha | date:"d/m/Y" }}  {{ x | upper }} (lower, capitalize, truncate:40, url, nl2br, raw)
{{{ html_crudo }}}                       sin escapar
{{#if empresa}} … {{else}} … {{/if}}     condicional   ({{#unless x}}…{{/unless}})
{{#each proyectos}}{{ this.nombre }} #{{@index}}{{/each}}   bucle
```

Variables del sistema: `unsubscribe_url`, `view_url`, `to_name`, `to_email`, `current_year`, `company_name`.
Con leads, listas o la API también llegan `first_name`, `last_name`, `name`, `email`, `phone`, `company`, `job_title`, `source`, `stage`,
`utm_*`, tus campos personalizados y `lead.*`.

**Tipo de plantilla:** *Marketing* agrega enlace de baja y cabeceras `List-Unsubscribe` (one-click). *Transaccional* es para avisos
individuales (bienvenida, cotización).

## 3. Boletines y audiencias

- **Audiencias** (*Email → Audiencias*): listas manuales; pega filas desde Excel/CSV (`email, nombre, empresa…`; si hay encabezados, las
  columnas extra pasan a ser variables).
- **Boletines** (*Email → Boletines → Nuevo*): plantilla, asunto, preheader y **audiencia combinada**: listas + leads del CRM
  (filtrables por origen, etapa, prioridad, responsable, etiquetas y fechas) + clientes. Se deduplica por correo y se excluyen
  bajas, rebotes y spam. Muestra el **conteo en vivo**, vista previa, envío de prueba, **enviar ahora o programar**.
- El envío corre en cola, por lotes de 50 (API *batch* de Resend, respetando su límite de solicitudes), con **pausa, reanudar y cancelar**.
  El contenido se congela al iniciar el envío.
- **Resultados**: embudo (enviados → entregados → abiertos → clics), rebotes, spam, bajas, enlaces más clicados y estado por destinatario.

## 4. API de envío

Crea una key en *Email → API e integraciones* (con permisos `emails.send`, `emails.read`). Base: `https://TU-DOMINIO/api/v1`,
autenticación `Authorization: Bearer qbk_…` (o `X-Api-Key`). **Úsala solo desde un servidor**, nunca en JavaScript público.

```bash
curl -X POST https://TU-DOMINIO/api/v1/emails/send \
  -H "Authorization: Bearer qbk_xxxxxxxx" -H "Content-Type: application/json" \
  -d '{
    "template": "bienvenida",
    "to": { "email": "maria@ejemplo.cl", "name": "María González" },
    "variables": { "first_name": "María", "proyecto": "Torre Norte",
                   "items": [{ "nombre": "Depto 2D", "precio": 98000000 }] }
  }'
```

| Parámetro | Descripción |
| --- | --- |
| `template` \* / `template_id` | Slug o id de la plantilla (debe estar activa). |
| `to` \* | Correo, `{email, name}` o arreglo (máx. 50). |
| `variables` | Valores de las variables. **Cualquier otro parámetro** (JSON, formulario o *query string*) también se toma como variable: `?template=x&to=a@b.cl&first_name=María`. |
| `lead_id` | Carga los datos de ese lead como variables. |
| `subject`, `from_email` | Reemplazan el asunto / remitente. |
| `delay_minutes` / `send_at` | Programa el envío. |
| `track_opens`, `track_clicks` | Medir aperturas/clics (por defecto `false` en transaccionales). |
| Header `Idempotency-Key` | Evita envíos duplicados si reintentas. |

Responde **202** `{"data":[{"id":"uuid","to":"…","status":"queued","warnings":{"missing_variables":[]}}]}` · `GET /emails/{id}` consulta
estado, aperturas, clics y eventos · `401` key inválida · `403` sin permiso · `404` plantilla · `422` validación · `429` 60/min por key.
Las direcciones en la lista de bajas devuelven `status: "suppressed"` y no se envían.

## 5. Automatizaciones

*Email → Automatizaciones*: reglas sin código que envían una plantilla cuando pasa algo con un lead.

- **Cuándo:** ingresa un lead nuevo · un lead cambia de etapa.
- **Condiciones:** origen(es) y/o etapa(s) de destino.
- **Qué:** plantilla, asunto opcional, retraso (minutos/horas/días), destino (correo del lead o una dirección fija para avisos internos)
  y valores fijos de variables (por defecto; el dato del lead prevalece).

Ejemplo **landing → correo de bienvenida**: el formulario de la landing llama (desde tu servidor) a `POST /api/v1/leads` con la key del
origen «Landing page» y los datos del formulario (incluyendo cualquier parámetro extra, p. ej. `proyecto`). El lead aparece en el Kanban
y la automatización «Cuando ingresa un lead desde Landing page → plantilla Bienvenida» le envía el correo usando esos datos como
variables. Alternativa directa: agregar `"email_template": "bienvenida"` en la misma llamada.

## 6. Seguimiento, bajas y webhook

- **Aperturas** (pixel) y **clics** (redirección firmada) se miden por mensaje; las aperturas pueden estar infladas por la protección
  de privacidad de Apple Mail.
- **Baja:** enlace `{{ unsubscribe_url }}` (se agrega solo en boletines si falta) con página de confirmación y *one-click*
  (`List-Unsubscribe-Post`). Las bajas, **rebotes permanentes** y **reclamos de spam** van a *Bajas y rebotes* y se excluyen de todo envío
  futuro; se pueden reactivar a mano.
- **Webhook de Resend** (entregas, rebotes, spam): en [resend.com/webhooks](https://resend.com/webhooks) agrega
  `https://TU-DOMINIO/webhooks/resend`, marca los eventos `email.*` y pega el *signing secret* (`whsec_…`) en *Email → Configuración*.
  Se verifica la firma Svix; sin secret configurado se rechazan todas las llamadas.
- **Historial de envíos:** todos los correos (boletines, API, automatizaciones, pruebas) con estado, eventos y variables usadas.

## Estructura

```
app/Services/LeadService.php            leads: creación, movimiento, asignación, historial, disparo de automatizaciones
app/Services/Email/                     motor de plantillas, proveedores (Resend/log), composición, audiencias, envío por lotes
app/Jobs/                               SendEmailMessage, SendCampaignBatch, StartCampaign
app/Http/Controllers/Api/               API de leads y de envío de emails
app/Http/Controllers/Email/             plantillas, boletines, listas, automatizaciones, historial, ajustes
app/Http/Controllers/Public|Webhooks/   tracking, baja, "ver en el navegador" y webhook de Resend
config/permissions.php                  catálogo de permisos y roles base
resources/js/lib/emailBuilder.ts        compilador de bloques → HTML de email
resources/js/components/kanban|email/   tablero Kanban y editor de plantillas
```

## Comandos útiles

```bash
composer run dev                  # servidor + worker de cola + Vite
php artisan schedule:work         # boletines programados (en producción: cron schedule:run)
php artisan queue:work            # worker de envíos
php artisan campaigns:dispatch-due
php artisan test                  # requiere composer install con dependencias de desarrollo
npm run types:check               # tipos TypeScript / Vue
npm run build                     # build de producción
```
