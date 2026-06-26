# Eval 04 — UTM fields (EN)

## Prompt

> Where are UTM fields defined in the PlanOK API?

## Respuesta mínima esperada

- Confirmar que los campos UTM aparecen documentados en el body de `POST /leads`:
  `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`.
- Indicar que para **leer** UTM desde otros módulos (gestor, cotizaciones, reservas, clientes) debe verificarse en `openapi.json` y `schemas.md` antes de afirmarlo.
- Seguridad del endpoint: `JWT`.

## Verificación

- La respuesta no debe afirmar disponibilidad de UTM en otros módulos si no aparece en el schema/respuesta correspondiente.
- Debe recomendar verificar en `references/openapi.json` o `references/endpoint-index.md`.