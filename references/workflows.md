# Workflows comunes PlanOK API

## Autenticacion CRM
1. Confirmar que se tiene `apikey` de inmobiliaria.
2. Llamar `POST /login?apikey=<API_KEY>` con body `Credentials`.
3. Guardar `token` y `refresh_token` de `LoginResponse`.
4. Usar `Authorization: Bearer <JWT>` en endpoints que declaran `JWT`.
5. Renovar con `POST /refresh-token?apikey=<API_KEY>` y body `{"refresh_token": "<REFRESH_TOKEN>"}`.

## Crear registro en centralizador / leads
Endpoint principal: `POST /leads` con seguridad `JWT`.
Campos de body documentados: `medio_llegada`, `fecha_ingreso`, `rut_dni`, `nombre`, `apellido_paterno`, `apellido_materno`, `telefono_fijo`, `telefono_movil`, `email`, `proyecto`, `proyecto_id`, `producto_id`, `producto_glosa`, `comentario`, `clausula_publicidad`, `clausula_proyecto`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`.

## Consultar centralizador / leads
- Todos o filtrados: `GET /leads` con query opcional `estado`, `fechaInicio`, `fechaFin`, `contacto`, `rutVendedor`.
- Por id: `GET /leads/{id}`.
- Vendedores asignables: `GET /leads/vendedores`.
- Reasignar vendedor: `POST /leads/reasignar-vendedor`.
- Descartar lead: `PATCH /leads/{id}/descartar`.

## UTM
Los campos UTM aparecen documentados en `POST /leads`: `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`. Cuando pregunten donde ver o enviar UTM, revisar tambien endpoints de gestor (`/gestor/leads`, `/gestor/cotizaciones`, `/gestor/reservas`) y schemas relacionados en `openapi.json`, pero no afirmar disponibilidad si no aparece en la respuesta/schema.

## Clientes y cotizaciones
- Buscar cliente natural: `GET /clientes/naturales`.
- Listar clientes naturales: `GET /clientes/naturales/listar`.
- Crear cliente natural: `POST /clientes/naturales`.
- Actualizar cliente natural: `PUT /clientes/naturales/{id}` o endpoints PATCH por seccion.
- Cotizaciones de cliente por RUT/DNI: `GET /clientes/naturales/cotizaciones/{rut_dni}`.
- Crear cotizacion: `POST /cotizaciones`.
- Obtener cotizacion: `GET /cotizaciones/{idCotizacion}`.
- PDF cotizacion: `GET /cotizaciones/{idCotizacion}/pdf`.
- Listar cotizaciones: `GET /cotizaciones/listar`.

## Proyectos, productos e inventario
- Proyectos del usuario: `GET /proyectos`.
- Etapas: `GET /proyectos/{id}/etapas`.
- Modelos: `GET /proyectos/{id}/modelos`.
- Productos principales: `GET /proyectos/{id}/productos-principales` con filtros opcionales `piso`, `orientacion`, `tipoProducto`, `programa`, `nombreModelo`, `disponibleWeb`, `disponibleSistema`, `estado`.
- Productos secundarios/adicionales por proyecto, etapa, modelo o subagrupacion: revisar `endpoint-index.md`.

## Negocios, reservas, promesas y escrituras
- Listar negocios: `GET /negocios` y `GET /v2/negocios`.
- Reservas por negocio: `GET /negocios/{id}/reservas`.
- Promesas por negocio: `GET /negocios/{id}/promesas`.
- Crear reserva: `POST /reservas/sin-cuotas`.
- Modificar/cancelar reserva: `POST /reservas/modificar`, `POST /reservas/cancelar`.
- Crear promesa: `POST /promesa`.
- Listar promesas y fechas: `GET /promesas`, `/promesas/fecha-inmobiliaria`, `/promesas/fecha-firma-comprador`, `/promesas/canceladas`.
- Crear escritura: `POST /escritura`; listar escrituras: `GET /escrituras`.

## Guia propietario y app movil
Usar endpoints `/guia/*` para registro, login Cognito, proyectos por RUT/DNI, datos de propiedad, forma de pago, consultas, archivos y notificaciones push. Ver seguridad: muchos requieren `CognitoJWT` o `NotificacionPush`.

## Pagos externos y Pok Pago
- Pok Pago: `/pok-pago/deudas/{codigo}`, `/pok-pago/pagos/{codigo}`, `/pok-pago/pagos/reversa/{codigo}`, `/pok-pago/pago` con `XAPIKey`.
- Pagos CRM: `/pagos/ingresados`, `/pagos/reversados`, `/pagos/conciliados`, `/formaPagos/externo`, `/cuotas/externo`, `/cuotas/externas`, conciliaciones/depositos por pago.
