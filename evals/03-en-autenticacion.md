# Eval 03 — Authentication (EN)

## Prompt

> How do I authenticate to PlanOK CRM?

## Respuesta mínima esperada

- Login: `POST /login?apikey=<API_KEY>` con body `Credentials`.
- Respuesta: `token` y `refresh_token` (campo `LoginResponse`).
- Uso posterior: header `Authorization: Bearer <JWT>` en endpoints con seguridad `JWT`.
- Refresh: `POST /refresh-token?apikey=<API_KEY>` con body `{"refresh_token": "<REFRESH_TOKEN>"}`.
- Base URL: `https://api-gci-rest.integracionplanok.io/api`.
- Ejemplo `curl` de login + header de uso posterior.

## Verificación

- La respuesta debe estar en inglés (la skill debe responder en el idioma del usuario).
- Debe citar `references/auth.md` o `references/workflows.md`.
- Debe usar placeholders `<API_KEY>` y `<JWT>`, nunca credenciales reales.