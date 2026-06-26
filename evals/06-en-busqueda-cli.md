# Eval 06 — CLI search (EN)

## Prompt

> How can I search for endpoints from the CLI included with this skill?

## Respuesta mínima esperada

- Comando: `python scripts/search_planok_api.py "<keyword>"`.
- Flag opcional: `--doc <path>` para usar otro archivo OpenAPI.
- Salida: lista de hits con método HTTP, ruta, operationId y descripción.
- Ejemplos:
  - `python scripts/search_planok_api.py "utm"`
  - `python scripts/search_planok_api.py "cotizaciones"`
  - `python scripts/search_planok_api.py "centralizador"`
  - `python scripts/search_planok_api.py "promesas"`
- Indicar que el script lee `references/openapi.json` por defecto.

## Verificación

- La respuesta debe explicar que el script está en `scripts/search_planok_api.py`.
- Debe mencionar que imprime hits de endpoints y de schemas.
- No debe proponer dependencias externas (el script solo usa `json` + `pathlib`).