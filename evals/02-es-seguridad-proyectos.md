# Eval 02 — Seguridad de endpoint de proyectos (ES)

## Prompt

> ¿Qué esquema de autenticación usa `GET /proyectos`?

## Respuesta mínima esperada

- Indicar el/los esquema(s) de seguridad declarados en `openapi.json` para esa operación (típicamente `JWT` o `APIKey`).
- Explicar cómo se envía cada uno:
  - `JWT` → header `Authorization: Bearer <JWT>`
  - `APIKey` → query `?apikey=<API_KEY>`
- Confirmar base URL y formato JSON.
- Si el endpoint tiene más de una seguridad declarada (combinación), mencionarlo.

## Verificación

- La respuesta debe consultar `references/openapi.json` o `references/endpoint-index.md` antes de afirmar.
- Debe distinguir claramente entre `query`, `path`, `headers` y `body`.
- No debe asumir seguridad si el Swagger la omite.