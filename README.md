# PlanOK API Skill

> Experto en la API REST de PlanOK CRM, con la especificación OpenAPI/Swagger completa embebida.
> Expert on the PlanOK CRM REST API, with the full OpenAPI/Swagger spec embedded.

Una **skill** instalable para agentes de IA (Claude Code, OpenCode, Cursor, Codex, Gemini, Windsurf, ChatGPT Custom GPT) que responde dudas técnicas sobre la API de PlanOK basándose en la documentación oficial.

An **installable skill** for AI agents (Claude Code, OpenCode, Cursor, Codex, Gemini, Windsurf, ChatGPT Custom GPT) that answers technical questions about the PlanOK API using the official documentation as source of truth.

---

## ¿Qué hace? / What does it do?

- Responde sobre **endpoints**, autenticación, parámetros, schemas y ejemplos de la API PlanOK.
- Cubre módulos: centralizador/leads, UTM, clientes, cotizaciones, proyectos, productos, reservas, promesas, pagos, Pok Pago, guía propietario, iConstruye y gestor.
- Incluye un **buscador CLI local** (`scripts/search_planok_api.py`) para encontrar endpoints y schemas por palabra clave.
- **No inventa** endpoints ni campos: si la documentación no lo declara, lo dice explícitamente.

---

## Plataformas soportadas / Supported platforms

| Plataforma | Cómo la usa |
|---|---|
| Claude Code / OpenCode / Cursor / Codex / Gemini / Windsurf / VS Code Copilot | Lee `SKILL.md` + `references/` + `scripts/` |
| ChatGPT Custom GPT | Lee `agents/openai.yaml` (interface) + knowledge uploads |
| Cualquier agente compatible con [Agent Skills spec](https://skills.sh/) | Mismo formato `SKILL.md` |

---

## Instalación / Installation

### Vía skills.sh (recomendado / recommended)

```bash
# Global (todos los proyectos) / Global (all projects)
npx skills add kuanticacl/skill-planok -g -y

# Solo proyecto actual / Project-only
npx skills add kuanticacl/skill-planok -y
```

### Manual

```bash
# Global en Claude Code
git clone https://github.com/kuanticacl/skill-planok.git \
  ~/.claude/skills/planok-api
```

### Verificar instalación / Verify

```bash
npx skills check
```

La skill quedará registrada como `planok-api` y se activará automáticamente cuando el agente detecte palabras clave como *endpoints*, *swagger*, *leads*, *cotizaciones*, *UTM*, *Pok Pago*, etc.

---

## Uso / Usage

### Ejemplo en español

> **Usuario:** ¿Cómo creo un lead en el centralizador?
>
> **Skill:** Debes llamar a `POST /leads` con seguridad `JWT` (header `Authorization: Bearer <JWT>`).
>
> ```bash
> curl -X POST "https://api-gci-rest.integracionplanok.io/api/leads" \
>   -H "Authorization: Bearer <JWT>" \
>   -H "Content-Type: application/json" \
>   -d '{
>     "rut_dni": "12345678-9",
>     "nombre": "Juan",
>     "apellido_paterno": "Pérez",
>     "email": "juan@example.cl",
>     "telefono_movil": "+56912345678",
>     "proyecto_id": 42,
>     "comentario": "Interesado en departamento"
>   }'
> ```

### Example in English

> **User:** How do I authenticate to PlanOK CRM?
>
> **Skill:** Call `POST /login?apikey=<API_KEY>` with credentials, then use `Authorization: Bearer <JWT>` in subsequent requests that declare `JWT` security.

---

## Estructura / Structure

```
skill-planok/
├── SKILL.md                          # Instrucciones + frontmatter (ES + EN)
├── README.md                         # Este archivo / This file
├── LICENSE                           # MIT
├── .gitignore
├── agents/
│   └── openai.yaml                   # Metadata para ChatGPT Custom GPT
├── references/                       # Fuente de verdad de la API
│   ├── openapi.json                  # Swagger 2.0 completo (~498 KB)
│   ├── endpoint-index.md             # Índice de operaciones
│   ├── schemas.md                    # Resumen de definitions
│   ├── auth.md                       # Esquemas de seguridad
│   └── workflows.md                  # Flujos comunes
├── scripts/
│   └── search_planok_api.py          # Buscador CLI local
└── evals/                            # Prompts de prueba
    ├── 01-es-crear-lead.md
    ├── 02-es-seguridad-proyectos.md
    ├── 03-en-autenticacion.md
    ├── 04-en-utm.md
    ├── 05-es-pok-pago.md
    └── 06-en-busqueda-cli.md
```

---

## Buscador CLI / CLI search tool

```bash
python scripts/search_planok_api.py "utm"
python scripts/search_planok_api.py "cotizaciones"
python scripts/search_planok_api.py "centralizador"
python scripts/search_planok_api.py "promesas"
```

---

## Configuración para ChatGPT Custom GPT

1. Abre [chat.openai.com/gpts/editor](https://chat.openai.com/gpts/editor).
2. Copia el contenido de `agents/openai.yaml` en el bloque de **Instructions** (o úsalo como guía).
3. En **Knowledge**, sube estos archivos (más livianos que el OpenAPI completo):
   - `references/endpoint-index.md`
   - `references/schemas.md`
   - `references/auth.md`
   - `references/workflows.md`
4. Activa **Actions** si quieres que el GPT haga llamadas reales a la API (opcional).

---

## Base técnica / Technical base

- **Base URL:** `https://api-gci-rest.integracionplanok.io/api`
- **Swagger version:** `1.16.14`
- **Seguridad:** `APIKey`, `APIKeyPublic`, `JWT`, `XAPIKey`, `CognitoJWT`, `NotificacionPush`, `Planok`, `SsoJWT`

---

## Licencia / License

MIT © 2026 KUANTICA — ver [LICENSE](./LICENSE).

---

## Links

- Repo: https://github.com/kuanticacl/skill-planok
- Skills registry: https://skills.sh/kuanticacl/skill-planok
- Issues: https://github.com/kuanticacl/skill-planok/issues