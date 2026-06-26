# Eval 05 — Pok Pago (ES)

## Prompt

> Dame el endpoint para listar los pagos de una unidad en Pok Pago.

## Respuesta mínima esperada

- Endpoints relevantes:
  - `GET /pok-pago/deudas/{codigo}`
  - `GET /pok-pago/pagos/{codigo}`
  - `POST /pok-pago/pago`
  - `POST /pok-pago/pagos/reversa/{codigo}`
- Seguridad: `XAPIKey` (header `X-API-KEY: <API_KEY>`).
- Indicar que `codigo` es el identificador de la unidad / deudor.
- Base URL: `https://api-gci-rest.integracionplanok.io/api`.

## Verificación

- La respuesta debe confirmar cada endpoint contra `references/endpoint-index.md` o `references/workflows.md`.
- Debe usar placeholder `<API_KEY>` y `<CODIGO>`.
- No debe inventar endpoints como `/pok-pago/listar` si no existen.