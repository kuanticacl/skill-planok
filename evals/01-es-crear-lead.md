# Eval 01 — Crear lead en centralizador (ES)

## Prompt

> ¿Cómo creo un lead en el centralizador de PlanOK?

## Respuesta mínima esperada

- Endpoint: `POST /leads`
- Seguridad: `JWT`
- Header: `Authorization: Bearer <JWT>`
- Base URL: `https://api-gci-rest.integracionplanok.io/api`
- Body mínimo (campos documentados): `rut_dni`, `nombre`, `apellido_paterno`, `email`, `telefono_movil`, `proyecto_id`, `comentario`
- Campos UTM opcionales: `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`
- Incluir ejemplo `curl` listo para copiar/pegar

## Verificación

- La respuesta debe citar `references/endpoint-index.md` o `references/workflows.md`.
- No debe inventar campos no documentados (por ejemplo, no afirmar que `telefono_fijo` es obligatorio si la doc no lo dice).
- Debe usar placeholders `<JWT>`, `<ID_PROYECTO>`, nunca credenciales reales.