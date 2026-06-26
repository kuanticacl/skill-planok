# Changelog

Todos los cambios relevantes de `planok-api` se documentan aquí.
Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.1.0/).

## [1.0.0] - 2026-06-25

### Added
- Skill pública instalable para Claude Code, OpenCode, Cursor, Codex, Gemini, Windsurf, VS Code Copilot y ChatGPT Custom GPT.
- `SKILL.md` con frontmatter bilingüe (ES + EN) y disparadores en ambos idiomas.
- `references/openapi.json` con el Swagger 2.0 completo de PlanOK (v1.16.14).
- `references/endpoint-index.md` — índice de todas las operaciones con método, ruta, seguridad, parámetros, body y respuesta 200.
- `references/schemas.md` — resumen de `definitions` y propiedades principales.
- `references/auth.md` — base URL y los 8 esquemas de seguridad documentados.
- `references/workflows.md` — flujos comunes (auth, leads, UTM, clientes, cotizaciones, proyectos, reservas, promesas, pagos, Pok Pago, guía propietario).
- `scripts/search_planok_api.py` — buscador CLI local sin dependencias externas.
- `agents/openai.yaml` — metadata para ChatGPT Custom GPT (display name, accent color, icon, repository links).
- `evals/` — 6 prompts de prueba (3 ES, 3 EN) cubriendo leads, autenticación, UTM, Pok Pago y el buscador CLI.
- `README.md` bilingüe con instrucciones de instalación, plataformas soportadas y configuración para ChatGPT.
- `.gitignore` con reglas estándar para macOS, Python, Node y editores.
- LICENSE MIT.
- Topics en GitHub: `claude-skill`, `agent-skill`, `openai-gpt`, `planok`, `crm-api`, `openapi`, `integration`, `rest-api`.

### Notes
- Repo: https://github.com/kuanticacl/skill-planok
- Instalación: `npx skills add kuanticacl/skill-planok -g -y`