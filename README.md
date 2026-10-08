# Quiebre CRM

CRM interno de **Quiebre** ([quiebre.cl](https://www.quiebre.cl)) para gestionar prospectos de inmobiliarias.
Laravel 13 · Inertia 3 · Vue 3 (TypeScript) · Tailwind 4 · shadcn-vue · SQLite (configurable).

Identidad visual tomada de quiebre.cl: fuente **Asap**, naranja `#FF5300`, wordmark oficial, botones tipo píldora.

## Qué incluye

| Módulo | Descripción |
| --- | --- |
| **Usuarios** | CRUD, activo/inactivo (un usuario inactivo no puede ingresar), rol asignado. |
| **Roles y permisos** | Matriz de permisos por rol. Los permisos se aplican en rutas (`can:`), policies y vistas (`can('...')`). |
| **Dashboard + Kanban** | Recuentos de leads (total, hoy, en gestión, concretados, descartados, conversión) **por origen**, filtros por período/responsable/búsqueda y tablero Kanban con *drag & drop*. |
| **Orígenes y API** | Cada origen (Sitio web, Meta Ads, Google Ads…) tiene su propia **API key**; el origen del lead se reconoce por la key con que llega. |
| **Etapas del Kanban** | Configurables: nombre, color, tipo (en curso / concretado / descartado) y orden (arrastrando). Por defecto: Ingreso, Contactado, Agendado, Propuesta, Concretado, Descartado. |
| **Campos personalizados** | Texto, número, fecha, lista, sí/no, etc. Aparecen en formularios, ficha, API y (opcional) en la tarjeta del Kanban. |
| **Leads** | Nombre, apellido, correo, teléfono, cargo, empresa + UTM + metadatos de captura (IP, ubicación, navegador, landing, referrer). Notas (con opción **privada**), asignación a un usuario e **historial de seguimiento** (llamadas, correos, reuniones… y eventos automáticos). |
| **Clientes** | CRUD de la base de clientes (inmobiliarias) con ficha y leads asociados. |

## Puesta en marcha

Requisitos: PHP ≥ 8.3, Composer, Node ≥ 22.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed        # roles, etapas, orígenes y usuario administrador
composer run dev                  # http://localhost:8000
```

Usuario administrador inicial (configurable con `ADMIN_EMAIL`, `ADMIN_NAME` y `ADMIN_PASSWORD` en `.env`):

- **admin@quiebre.cl** / `password` (solo en entornos no productivos; en producción `ADMIN_PASSWORD` es obligatorio).

Datos de demostración opcionales (usuarios, clientes y 48 leads de ejemplo):

```bash
php artisan db:seed --class=DemoSeeder      # usuarios demo con contraseña "password"
```

## Roles y permisos

Los permisos viven en [`config/permissions.php`](config/permissions.php), agrupados (`leads.view`, `clients.update`, …).
Para agregar uno nuevo basta sumarlo ahí: aparece solo en la matriz de roles y queda disponible como:

```php
Route::get(...)->middleware('can:clients.create');   // rutas
$user->hasPermission('leads.assign');                // código
```
```ts
const { can } = usePermissions();                    // Vue
can('leads.delete')
```

- El rol **Administrador** (slug `admin`) siempre tiene todos los permisos y no se puede editar ni eliminar.
- `leads.view` muestra solo los leads **asignados** al usuario; `leads.view_all` muestra todos.
- Roles base: Administrador, Supervisor, Ejecutivo comercial y Solo lectura (editables desde el CRM).

## API de ingreso de leads

`POST /api/v1/leads` · el origen se identifica por la **API key** (se ve y se regenera en *Orígenes y API*).

```bash
curl -X POST https://TU-DOMINIO/api/v1/leads \
  -H "X-Api-Key: qb_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "María",
    "last_name": "González",
    "email": "maria@ejemplo.cl",
    "phone": "+56912345678",
    "company": "Inmobiliaria Ejemplo",
    "job_title": "Gerente comercial",
    "message": "Quiero cotizar una campaña",
    "utm_source": "google", "utm_medium": "cpc", "utm_campaign": "proyecto-otono",
    "landing_url": "https://tusitio.cl/contacto",
    "referrer": "https://www.google.com/",
    "ip": "200.1.2.3",
    "custom": { "proyecto_de_interes": "Torre Norte" }
  }'
```

- Requerido: `first_name` (o `name`, que se separa en nombre/apellido) y al menos `email` o `phone`.
- También se aceptan `utm_source|medium|campaign|term|content`, `ip`, `user_agent`, `referrer`, `landing_url`, `country`, `region`, `city`, `latitude`, `longitude`.
- Los campos personalizados se envían dentro de `custom` (o sueltos) usando su **clave** (visible en *Campos personalizados*). Cualquier dato no reconocido se guarda en los metadatos del lead.
- Si no se envía `ip`/`user_agent`, se toman de la petición; la ubicación se completa con los headers del CDN/proxy cuando existen (`CF-IPCountry`, `CF-IPCity`, `X-Vercel-IP-*`…). **Si la llamada se hace desde el backend de un sitio, envía la `ip` del visitante** para registrar su ubicación real.
- Respuestas: `201` creado · `401` key ausente/inválida · `403` origen desactivado · `422` validación · `429` límite (120/min por key).
- El lead ingresa en la **primera etapa** del Kanban, arriba de la columna.

## Estructura

```
app/Services/LeadService.php         creación, movimiento y asignación de leads + historial
app/Http/Controllers/Api/            API de ingreso
app/Policies/LeadPolicy.php          visibilidad de leads según permisos
config/permissions.php               catálogo de permisos y roles base
resources/js/pages/                  vistas Inertia (Dashboard, leads, clients, crm, users, roles)
resources/js/components/kanban/      tablero Kanban
```

## Comandos útiles

```bash
npm run types:check      # tipos TypeScript / Vue
npm run build            # build de producción
composer run dev         # servidor + cola + Vite
```
