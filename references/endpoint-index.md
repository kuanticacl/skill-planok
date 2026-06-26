# Indice completo de endpoints PlanOK API

Base URL: `https://api-gci-rest.integracionplanok.io/api`
Operaciones documentadas: 209

Usar este indice para ubicar rapidamente metodo, ruta, seguridad, parametros y schema de respuesta. Para detalles completos, consultar `openapi.json`.

## CanalesPago

### POST `/canales-pago/eliminar-cuotas`
- operationId: `eliminarCuotasCanalPago`
- descripcion: Elimina cuotas de canales de pago si corresponde
- seguridad: JWT
- respuesta 200: `no documentado`
- body:
  - data: cuotas (array), id_promesa (object), id_oferta (object), id_canal_pago (integer)

## Guia

### GET `/guia/agenda-contacto`
- operationId: `findByIdInmobiliriaAndIdProyecto`
- descripcion: Endpoint para listar agenda de contacto por proyecto de una inmobiliaria
- seguridad: CognitoJWT
- respuesta 200: `#/definitions/AgendaContacto`
- parametros:
  - query:apiKey (string, required) - ApiKey de la Inmobiliaria
  - query:idProyecto (integer, required) - Identificador del proyecto

### GET `/guia/archivos/documentos`
- operationId: `listar`
- descripcion: Endpoint para listar documentos
- seguridad: CognitoJWT
- respuesta 200: `#/definitions/Archivos`
- parametros:
  - query:rut (string, required) - RUT cliente
  - query:apiKey (string, required) - apikey de inmobiliaria
  - query:proyectoId (integer, required) - identificador del proyecto

### GET `/guia/archivos/fotos`
- operationId: `listar`
- descripcion: Endpoint para listar fotos
- seguridad: CognitoJWT
- respuesta 200: `#/definitions/Fotos`
- parametros:
  - query:rut (string, required) - RUT cliente
  - query:apiKey (string, required) - apikey de inmobiliaria
  - query:proyectoId (integer, required) - identificador del proyecto

### GET `/guia/archivos/reserva`
- operationId: `descargar`
- descripcion: Descargar archivo para de una reserva
- seguridad: no declarado
- respuesta 200: `string`
- parametros:
  - query:id (integer, required) - id de la reserva
  - query:apiKey (string, required) - ApiKey de la inmobiliaria

### GET `/guia/archivos/videos`
- operationId: `listar`
- descripcion: Endpoint para listar videos
- seguridad: CognitoJWT
- respuesta 200: `#/definitions/Videos`
- parametros:
  - query:rut (string, required) - RUT cliente
  - query:apiKey (string, required) - apikey de inmobiliaria
  - query:proyectoId (integer, required) - identificador del proyecto

### GET `/guia/banners`
- operationId: `getBanners`
- descripcion: Endpoint para listar los banner de un proyecto
- seguridad: CognitoJWT
- respuesta 200: `#/definitions/Banners`
- parametros:
  - query:rut (string, required) - RUT cliente sin dígito verificador
  - query:apiKey (string, required) - apikey de inmobiliaria
  - query:proyectoId (integer, required) - identificador del proyecto

### POST `/guia/cambiar-password`
- operationId: `cambiarPassword`
- descripcion: Cambia la contraseña de un propietario
- seguridad: APIKey
- respuesta 200: `no documentado`
- body:
  - data: mewPassword (string), mail (string)

### POST `/guia/confirmar-correo-propietario`
- operationId: `confirmarCorreoPropietario`
- descripcion: Confirma el correo del usuario registrado en la base de datos pivote, responde un código de verificación
- seguridad: APIKey
- respuesta 200: `no documentado`
- body:
  - data: codigoValidacion (string), mail (string)

### GET `/guia/datos-forma-pago`
- operationId: `findFormaPagoByRutAndIdProyectoAndApiKey`
- descripcion: Endpoint para listar la forma de pago según el rut del usuario
- seguridad: CognitoJWT
- respuesta 200: `#/definitions/DatosFormaPago`
- parametros:
  - query:rutDNI (string, required) - Identificador del usuario (RUT/DNI)
  - query:idProyecto (integer, required) - Identificador del proyecto
  - query:apiKey (string, required) - Api key de la Inmobiliaria

### GET `/guia/datos-propiedad`
- operationId: `findDatosPropiedadByRutAndIdProyectoAndApiKey`
- descripcion: Endpoint para listar los proyectos por inmobiliaria según el rut del usuario
- seguridad: CognitoJWT
- respuesta 200: `#/definitions/DatosPropiedad`
- parametros:
  - query:rutDNI (string, required) - Identificador del usuario (RUT/DNI)
  - query:idProyecto (integer, required) - Identificador del proyecto
  - query:apiKey (string, required) - Api key de la Inmobiliaria

### POST `/guia/enviar-correo-bienvenida`
- operationId: `enviarCorreoBienvenida`
- descripcion: Enviar Correo Bienvenida
- seguridad: no declarado
- respuesta 200: `no documentado`
- body:
  - EnviarCorreoBienvenida: #/definitions/EnviarCorreoBienvenida

### POST `/guia/enviar-notificacion-push`
- operationId: `enviar`
- descripcion: Enviar notificacion push por aplicación
- seguridad: NotificacionPush
- respuesta 200: `no documentado`
- parametros:
  - query:idInmobiliaria (integer, required) - Identificador de Inmobiliaria
- body:
  - body: #/definitions/NotificacionPush

### GET `/guia/historial-consulta`
- operationId: `find`
- descripcion: Endpoint para listar historial de consulta
- seguridad: CognitoJWT
- respuesta 200: `#/definitions/HistorialConsulta`
- parametros:
  - query:apiKey (string, required) - ApiKey de la Inmobiliaria
  - query:rut (string, required) - RUT del cliente sin dígito verificador
  - query:idProyecto (integer, required) - Identificador del proyecto

### GET `/guia/historial-notificaciones-push`
- operationId: `historial`
- descripcion: Endpoint para listar historial de consulta
- seguridad: NotificacionPush
- respuesta 200: `#/definitions/HistorialNotificacionesPush`
- parametros:
  - query:rut (string, required) - RUT cliente sin dígito verificador
  - query:apiKey (string, required) - apikey de inmobiliaria
  - query:proyectoId (integer, required) - identificador del proyecto

### POST `/guia/ingresar-consulta`
- operationId: `ingresar`
- descripcion: Endpoint para ingreso de consulta
- seguridad: CognitoJWT
- respuesta 200: `no documentado`
- parametros:
  - query:apiKey (string, required) - ApiKey de la Inmobiliaria
- body:
  - body: rutCliente (string, req), idProducto (integer, req), idCategoria (integer, req), consulta (string, req)

### GET `/guia/inmobiliarias-proyectos-usuario/api-key/{rutDNI}`
- operationId: `proyectosPorInmobiliariasByRut`
- descripcion: Endpoint para listar los proyectos por inmobiliaria según el rut del usuario y la ApiKey de la inmobiliaria
- seguridad: CognitoJWT
- respuesta 200: `#/definitions/ProyectosPorInmobiliaria`
- parametros:
  - path:rutDNI (string, required) - Identificador del usuario (RUT/DNI)
  - query:apikey (string, required) - Apikey de la inmobiliaria

### GET `/guia/inmobiliarias-proyectos-usuario/{rutDNI}`
- operationId: `proyectosPorInmobiliariasByRut`
- descripcion: Endpoint para listar los proyectos por inmobiliaria según el rut del usuario
- seguridad: CognitoJWT,Planok
- respuesta 200: `#/definitions/ProyectosPorInmobiliaria`
- parametros:
  - path:rutDNI (string, required) - Identificador del usuario (RUT/DNI)
  - query:apikey (string, optional) - Apikey de la inmobiliaria

### POST `/guia/login`
- operationId: `login`
- descripcion: Recibe user y pass y devuelve sesión
- seguridad: no declarado
- respuesta 200: `no documentado`
- body:
  - loginCognito: #/definitions/LoginCognito

### POST `/guia/notificacion-abierta`
- operationId: `notificacionAbierta`
- descripcion: marca la notificacion push como leida
- seguridad: NotificacionPush
- respuesta 200: `no documentado`
- body:
  - data: rut (string), idNotificacion (integer)

### POST `/guia/preguntas-frecuentes`
- operationId: `getPreguntasFrecuentes`
- descripcion: Get Preguntas Frecuentes
- seguridad: no declarado
- respuesta 200: `#/definitions/PreguntasFrecuentes`
- body:
  - data: apiKey (string), idProyecto (integer)

### POST `/guia/publicacion/{id}/enviar-notificaciones`
- operationId: `notificarClientes`
- descripcion: Endpoint para enviar notificaciones por publicación
- seguridad: APIKey,NotificacionPush
- respuesta 200: `string`
- parametros:
  - path:id (integer, required)

### POST `/guia/re-enviar-codigo-validacion`
- operationId: `enviarCodigoValidacion`
- descripcion: Envia codigo de validación al mail del propietario para la confirmación de su cuenta
- seguridad: APIKey
- respuesta 200: `no documentado`
- body:
  - email: email (string/email)

### POST `/guia/refresh-token`
- operationId: `refreshToken`
- descripcion: Recibe refreshToken y devuelve nuevo token
- seguridad: no declarado
- respuesta 200: `no documentado`
- body:
  - refreshTokenCognito: #/definitions/RefreshTokenCognito

### POST `/guia/registrar-propietario-pivote`
- operationId: `register`
- descripcion: Registra al usuario desde GCI a la BD de propietarios
- seguridad: no declarado
- respuesta 200: `no documentado`
- body:
  - propietario: #/definitions/Propietario

### POST `/guia/registro-dispositivo`
- operationId: `registroDispositivo`
- descripcion: Registra el id del dispositivo logueado
- seguridad: no declarado
- respuesta 200: `no documentado`
- body:
  - RegistroDispositivo: #/definitions/RegistroDispositivo

### POST `/guia/registro-inicial-propietario`
- operationId: `registroInicial`
- descripcion: Registro inicial, recibe el rut y lo crea en cognito, solo si existe en BD pivote
- seguridad: no declarado
- respuesta 200: `no documentado`
- body:
  - propietarioRut: #/definitions/PropietarioRegistro

### POST `/guia/set-force-change-pass`
- operationId: `setForceChangePass`
- descripcion: Actualiza ForceChangePass en BD pivote
- seguridad: no declarado
- respuesta 200: `no documentado`
- body:
  - email: email (string/email)

### GET `/guia/tipo-consultas`
- operationId: `findTipoConsultasByApiKey`
- descripcion: Endpoint para listar los tipos de consultas por inmobiliaria
- seguridad: CognitoJWT
- respuesta 200: `#/definitions/TipoConsultas`
- parametros:
  - query:apiKey (string, required) - ApiKey de la Inmobiliaria

### GET `/guia/trae-estado-propietario`
- operationId: `getEstadoPropietario`
- descripcion: Trae el estado del propietario
- seguridad: no declarado
- respuesta 200: `no documentado`
- parametros:
  - query:rut (string, required) - Rut del propietario sin guin ni digito verificador

## Iconstruye

### POST `/iconstruye/facturaciones/producto-principal`
- operationId: `procesarFacturacionProductoPrincipal`
- descripcion: Procesa una facturacion en base a la fecha firma comprador y la envia a iconstruye
- seguridad: XAPIKey
- respuesta 200: `#/definitions/Error`
- body:
  - data: idEscrituraSeguimiento (number), idPromesa (number), idProyecto (number), fechaHito (string/date), idCliente (number), tipoCliente (string enum=['NATURAL', 'JURIDICA'])

### POST `/iconstruye/facturaciones/productos-secundarios`
- operationId: `procesarFacturacionProductosSecundarios`
- descripcion: Procesa una facturacion en base a la fecha firma productos secundarios y la envia a iconstruye
- seguridad: XAPIKey
- respuesta 200: `#/definitions/Error`
- body:
  - data: idEscrituraSeguimiento (number), idPromesa (number), idProyecto (number), fechaHito (string/date), idCliente (number), tipoCliente (string enum=['NATURAL', 'JURIDICA'])

## Pok Pagos

### POST `/pok-pago/deudas/{codigo}`
- operationId: `findPokPagosByProyectIdAndUserDni`
- descripcion: Endpoint de Pok Pago para traer listado de deudas para Otros Pagos
- seguridad: XAPIKey
- respuesta 200: `#/definitions/PokPagosResponse`
- parametros:
  - path:codigo (string, required)
- body:
  - body: codigo_interno (string), cliente (string)

### PUT `/pok-pago/pago`
- operationId: `actualizarPago`
- descripcion: Endpoint de Pok Pago para actualizar un pago
- seguridad: XAPIKey
- respuesta 200: `no documentado`
- body:
  - body: id_pago (integer), fecha_recaudacion (string/date), monto_pesos (number/float), id_transaccion (string), fecha_conversion (string/date), monto_conversion (number/float)

### POST `/pok-pago/pagos/reversa/{codigo}`
- operationId: `reversaPokPagosByCuotaId`
- descripcion: Endpoint de Pok Pago para reversar Pago
- seguridad: XAPIKey
- respuesta 200: `no documentado`
- parametros:
  - path:codigo (string, required)
- body:
  - body: cliente (string), codigo_interno (string), id_transaccion (string), fecha_pago (string), monto (integer)

### POST `/pok-pago/pagos/{codigo}`
- operationId: `registerPayment`
- descripcion: Endpoint de Pok Pago guardar Pago realizado con éxito
- seguridad: XAPIKey
- respuesta 200: `no documentado`
- parametros:
  - path:codigo (string, required)
- body:
  - body: cliente (string), codigo_interno (string), id_transaccion (string), fecha_pago (string), fecha_creacion (string), fecha_ultima_modificacion (string), monto (integer)

## Provisioning

### POST `/provisioning/user`
- operationId: `provisionUser`
- descripcion: Proyecta o actualiza un usuario desde el SSO
- seguridad: SsoJWT
- respuesta 200: `no documentado`
- body:
  - data: #/definitions/CanonicalUserProjection

## Reserva En Línea

### GET `/ciudades`
- operationId: `findCiudad`
- descripcion: Obtener ciudades con sus regiones asociadas
- seguridad: APIKeyPublic
- respuesta 200: `array[#/definitions/Ciudad]`

### GET `/comunas`
- operationId: `findComuna`
- descripcion: Obtener comunas con sus ciudades y regiones asociadas
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Comuna`

### GET `/comunas/{id}/ciudad`
- operationId: `findComunasByIdCiudad`
- descripcion: Obtener las comunas asociadas a un identificador de ciudad
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Comuna`
- parametros:
  - path:id (integer, required) - identificador de ciudad

### GET `/comunas/{id}/region`
- operationId: `findComunasByIdRegion`
- descripcion: Obtener las comunas asociadas a un identificador de Región
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Comuna`
- parametros:
  - path:id (integer, required) - identificador de Región

### GET `/cotizaciones/{hash}/datos-cotizacion`
- operationId: `findByCotizacionId`
- descripcion: Obtener los datos de una cotización
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/DatosCotizacion`
- parametros:
  - path:hash (string, required) - identificador de negocio

### GET `/cotizaciones/{hash}/datos-cotizacion-peru`
- operationId: `findByCotizacionIdPeru`
- descripcion: Obtener los datos de una cotización
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/DatosCotizacion`
- parametros:
  - path:hash (string, required) - identificador de negocio

### GET `/departamentos`
- operationId: `findDepartamento`
- descripcion: Obtener Departamentos
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Departamento`

### GET `/distritos`
- operationId: `findDistrito`
- descripcion: Obtener distritos con sus provincias y departamentos asociados
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Distrito`

### GET `/distritos/{id}/departamento`
- operationId: `findDistritosByIdDepartamento`
- descripcion: Obtener los distritos asociados a un identificador de departamento
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Distrito`
- parametros:
  - path:id (integer, required) - identificador de departamento

### GET `/distritos/{id}/provincia`
- operationId: `findDistritosByIdProvincia`
- descripcion: Obtener los distritos asociados a un identificador de provincia
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Distrito`
- parametros:
  - path:id (integer, required) - identificador de provincia

### PUT `/producto/{hash}/actualizar-bloqueo`
- operationId: `actualizarBloqueoTemporal`
- descripcion: Actualiza el tiempo de Bloquedo del producto a Reservar
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/ProductoPrincipalMinutos`
- parametros:
  - path:hash (integer, required) - Hash Identificador de Cotización
- body:
  - minutos: #/definitions/ProductoPrincipalMinutos

### POST `/producto/{hash}/bloqueo-temporal`
- operationId: `bloqueoTemporal`
- descripcion: Actualizar el estado del producto a Reservar
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/ProductoPrincipalBloqueo`
- parametros:
  - path:hash (integer, required) - Hash Cotización

### PUT `/producto/{hash}/desbloqueo-temporal`
- operationId: `desbloqueoTemporal`
- descripcion: Actualizar el estado del producto a Reservar
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/ProductoPrincipalBloqueo`
- parametros:
  - path:hash (integer, required) - Hash Cotización

### GET `/provincias`
- operationId: `findProvincia`
- descripcion: Obtener los provincias con sus departamentos asociados
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Provincia`

### GET `/provincias/{id}/departamento`
- operationId: `findProvinciaByIdDepartamento`
- descripcion: Obtener las provincias asociadas a un identificador de departamento
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Provincia`
- parametros:
  - path:id (integer, required) - identificador de departamento

### GET `/regiones`
- operationId: `findRegion`
- descripcion: Obtener Regiones
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Region`

### GET `/reserva/background/imagen`
- operationId: `backgroundImagen`
- descripcion: Obtener la imagen de fondo de la reserva en línea
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/BackgroundReserva`

### PUT `/reserva/clientes/naturales/{hash}`
- operationId: `updateClienteNaturalReservaEnLineaById`
- descripcion: Actualizar cliente natural por hash cotizacion
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/ClienteNaturalRequestReservaEnlinea`
- parametros:
  - path:hash (integer, required) - Identificador hash de cotización
- body:
  - cliente: #/definitions/ClienteNaturalRequestReservaEnlinea

### POST `/reserva/validar-token-captcha/{token}`
- operationId: `validarTokenCaptcha`
- descripcion: Validar Token Captcha de la Reserva
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/ProductoPrincipalBloqueo`
- parametros:
  - path:token (string, required) - Token Captcha Cotización

### GET `/reserva/{hash}/datos-previos-a-reservar`
- operationId: `requisitosParaReservar`
- descripcion: Obtener los datos necesarios para poder crear una Reserva
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/DatosConfiguracionReserva`
- parametros:
  - path:hash (string, required) - identificador de la cotizacion

### PUT `/separacion/clientesPeru/naturales/{hash}`
- operationId: `updateClienteNaturalPeruSeparacionEnLineaById`
- descripcion: Actualizar cliente natural por hash cotizacion
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/ClienteNaturalRequestSeparacionEnlinea`
- parametros:
  - path:hash (integer, required) - Identificador hash de cotización
- body:
  - cliente: #/definitions/ClienteNaturalRequestSeparacionEnlinea

## Visanet

### POST `/reserva/visanet/iniciar-transaccion/{hash}`
- operationId: `iniciarTransaccionVisanet`
- descripcion: Crear cobro en PagosOK para iniciar transacción en medio de pago Visanet
- seguridad: APIKeyPublic
- respuesta 200: `no documentado`
- parametros:
  - path:hash (integer, required) - Identificador Hash Cotización

### POST `/reserva/visanet/notificar-transaccion/{hash}`
- operationId: `notificarTransaccionVisanet`
- descripcion: Notificar transacción para medio de pago Visanet
- seguridad: APIKeyPublic
- respuesta 200: `no documentado`
- parametros:
  - path:hash (string, required) - identificador de negocio
- body:
  - NotificarCobro: #/definitions/NotificarCobro

## Webpay

### POST `/reserva/webpay/mall/deuda/{hash}`
- operationId: `enviarDeudaWebpay`
- descripcion: Crear Deuda en PagosOK Webpay Mall
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Error`
- parametros:
  - path:hash (integer, required) - Identificador Hash Cotización

### POST `/reserva/webpay/mall/notificacion-de-pago/{hash}`
- operationId: `notificarPagoWebpay`
- descripcion: Notificar Estado de una Deuda
- seguridad: APIKeyPublic
- respuesta 200: `no documentado`
- parametros:
  - path:hash (string, required) - identificador de negocio
- body:
  - deuda: #/definitions/NotificarPago

## Whatsapp

### POST `/whatsapp/mensaje/guardar-template`
- operationId: `guardarTemplate`
- descripcion: Endpoint para guardar template automático de Whatsapp
- seguridad: XAPIKey
- respuesta 200: `no documentado`
- body:
  - body: whatsapp_id (string), template_name (string), body (string), rut_vendedor (string), telefono_cliente (string), timestamp (string)

### POST `/whatsapp/mensaje/recibir`
- operationId: `recibir`
- descripcion: Endpoint de recibir mensaje de Whatsapp
- seguridad: XAPIKey
- respuesta 200: `no documentado`
- body:
  - body: {"type": "array", "items": {"type": "object", "properties": {"object": {"type": "string"}, "entry": {"type": "array", "items": {"type": "object", "properties": {"id": {"type": "string"}, "changes": {"type": "array", "items": {"type": "object", "properties": {"value": {"type": "object", "properties": {"messaging_product": {"type": "string"}, "metadata": {"type": "object", "properties": {"display_phone_number": {"type": "string"}, "phone_number_id": {"type": "string"}}}, "contacts": {"type": "arra

## default

### GET `/canales/configuraciones/pagos/{idCanal}/{idProyecto}`
- operationId: `getConfiguraciones`
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- parametros:
  - path:idCanal (integer, required)
  - path:idProyecto (integer, required)

### GET `/ciudades/{id}/region`
- operationId: `findCiudadByIdRegion`
- descripcion: Obtener las ciudades asociadas a un identificador de región
- seguridad: APIKeyPublic
- respuesta 200: `#/definitions/Ciudad`
- parametros:
  - path:id (integer, required) - identificador de región

### POST `/clientes/documento`
- operationId: `cargarDocumento`
- descripcion: Cargar documento para un cliente natural
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - formData:idCliente (string, required) - ID Del cliente
  - formData:idTipoDocumento (string, required) - ID del tipo de documento
  - formData:documento (file/binary, required) - Formatos de carga : .xls .doc .pdf .png .jpg / tamaño máximo 25MB

### POST `/clientes/externo`
- operationId: `clientesExterno`
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- body:
  - data: id_cliente (integer), id_externo (string), id_canal (integer), estado_externo (string)

### PUT `/clientes/externo`
- operationId: `clientesExternoUpdate`
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- body:
  - data: id_cliente (integer), id_externo (string), id_canal (integer), estado_externo (string)

### GET `/clientes/juridicos`
- operationId: `findJuridicoByIdentificadorPersonal`
- descripcion: Obtener cliente juridico por identificador personal
- seguridad: JWT
- respuesta 200: `#/definitions/ClienteJuridico`
- parametros:
  - query:identificador-personal (integer, required) - identificador personal

### GET `/clientes/juridicos/listar`
- operationId: `obtenerClientesJuridicos`
- descripcion: Obtener cliente juridicos
- seguridad: JWT
- respuesta 200: `#/definitions/ClienteJuridico`
- parametros:
  - query:pagina (integer, required) - pagina actual
  - query:busquedaGeneral (string, optional) - busqueda general
  - query:rut (integer, optional) - identificador personal
  - query:razonSocial (string, optional) - Razon social del cliente

### PUT `/clientes/juridicos/{id}`
- operationId: `updateClienteJuridicoById`
- descripcion: Actualizar cliente natural por id
- seguridad: JWT
- respuesta 200: `#/definitions/ClienteJuridico`
- parametros:
  - path:id (integer, required) - id cliente juridico
- body:
  - cliente: #/definitions/ClienteJuridicoActualizarRequest

### GET `/clientes/naturales`
- operationId: `findNaturalByIdentificadorPersonal`
- descripcion: Obtener cliente natural por identificador personal
- seguridad: JWT
- respuesta 200: `#/definitions/ClienteNatural`
- parametros:
  - query:identificador-personal (integer, required) - identificador personal

### POST `/clientes/naturales`
- operationId: `createClienteNatural`
- descripcion: Crear cliente natural
- seguridad: JWT
- respuesta 200: `no documentado`
- body:
  - cliente: #/definitions/ClienteNaturalRequest

### GET `/clientes/naturales/cotizaciones/{rut_dni}`
- operationId: `findCotizacionesByRutCliente`
- descripcion: Obtener las cotizaciones de un cliente por medio del rut o DNI
- seguridad: JWT
- respuesta 200: `array[#/definitions/ClientesNaturalesCotizaciones]`
- parametros:
  - path:rut_dni (integer, required) - Rut o DNI del cliente

### GET `/clientes/naturales/listar`
- operationId: `obtenerClientesNaturales`
- descripcion: Obtener cliente naturales
- seguridad: JWT
- respuesta 200: `#/definitions/ClienteNatural`
- parametros:
  - query:pagina (integer, required) - pagina actual
  - query:busquedaGeneral (string, optional) - busqueda general
  - query:rut (integer, optional) - identificador personal
  - query:nombre (string, optional) - Nombre del cliente
  - query:apellidoPaterno (string, optional) - Apellido paterno del cliente
  - query:apellidoMaterno (string, optional) - Apellido materno del cliente
  - query:email (string, optional) - Email del cliente
  - query:celular (string, optional) - Celular del cliente

### PUT `/clientes/naturales/{id}`
- operationId: `updateClienteNaturalById`
- descripcion: Actualizar cliente natural por id
- seguridad: JWT
- respuesta 200: `#/definitions/ClienteNatural`
- parametros:
  - path:id (integer, required) - id cliente natural
- body:
  - cliente: #/definitions/ClienteNaturalRequest

### PATCH `/clientes/naturales/{id}/datos-personales`
- operationId: `actualizarDatosPersonalesClienteNatural`
- descripcion: Actualiza los datos personales del cliente natural por su id
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - Identificador del cliente natural
- body:
  - clienteDatosPersonales: rut (integer), dv (string), nombre (string), apellidoPaterno (string), apellidoMaterno (string), fechaNacimiento (string/date), idTipoCliente (integer), edad (string enum=['1-25', '26-35', '36-45', '46-55', '56-65', '66 a más']), genero (string enum=['MASCULINO', 'FEMENINO']), nacionalidad (string), idPaisOrigen (integer)

### PATCH `/clientes/naturales/{id}/direcciones`
- operationId: `actualizarDireccionClienteNatural`
- descripcion: Actualiza la direccion del cliente natural por su id
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - Identificador del cliente natural
- body:
  - clienteDatosPersonales: direccion (string), direccionNumero (string), direccionDepartamento (string), direccionSector (string), idRegion (integer), idProvincia (integer), idComuna (integer), telefonoFijo (string), telefonoCelular (string), email (string)

### PATCH `/clientes/naturales/{id}/empresa-actual`
- operationId: `actualizarEmpresaActualClienteNatural`
- descripcion: Actualiza la empresa actual del cliente natural por su id
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - Identificador del cliente natural
- body:
  - clienteEmpresaActual: rut (integer), dv (string), nombreEmpresa (string), giro (string), direccion (string), idRegion (integer), idProvincia (integer), idComuna (integer), telefonoOficina (string)

### PATCH `/clientes/naturales/{id}/informacion-conyuge`
- operationId: `actualizarInformacionConyugeClienteNatural`
- descripcion: Actualiza la informacion del conyuge del cliente natural por su id
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - Identificador del cliente natural
- body:
  - clienteDatosConyuge: rut (integer), dv (string), nombre (string), apellidoPaterno (string), apellidoMaterno (string)

### PATCH `/clientes/naturales/{id}/informacion-familiar`
- operationId: `actualizarInformacionFamiliarClienteNatural`
- descripcion: Actualiza la informacion familiar del cliente natural por su id
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - Identificador del cliente natural
- body:
  - clienteInformacionPersonal: estadoCivil (string enum=['SOLTERO', 'CASADO', 'DIVORCIADO', 'VIUDO', 'SEPARADO', 'CONVIVIENTE', 'CONVIVIENTE_HECHO']), regimenPatrimonial (string enum=['SEPARACION DE BIENES', 'SOCIEDAD CONYUGAL', 'ANTICIPO DE HERENCIA', 'PARTICIPACION EN LOS GANANCIALES', 'ARTICULO 150']), hijos (integer), numeroIntegrantes (integer), ingresoFamiliar (number enum=[0, 1, 2, 3, 4, 5]), ingresoIndividual (number enum=[0, 1, 2, 3, 4, 5]), rentaLiquida (integer), arriendoLiquidos (integer), jubilacionPension (integer), honorarioLiquidos (integer), sueldoLiquidoVariable (integer), pagoDividendoArriendo (integer)

### PATCH `/clientes/naturales/{id}/informacion-laboral`
- operationId: `actualizarInformacionLaboralClienteNatural`
- descripcion: Actualiza la informacion laboral del cliente natural por su id
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - Identificador del cliente natural
- body:
  - clienteInformacionLaboral: idProfesion (integer), tipoContrato (string enum=['INDEFINIDO', 'A PLAZO', 'A HONORARIOS', 'OTRO']), idOficio (integer), actividadCargoDesempena (string), idGradoEstudio (integer), fechaInicioActividades (string/date), fechaIngresoTrabajoActual (string/date)

### GET `/clientes/negocios`
- operationId: `obtenerNegociosPorCliente`
- descripcion: Obtiene los negocios por cliente
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - query:tipoCliente (string enum=['NATURAL', 'JURIDICO'], optional) - Tipo de cliente (Natural o Juridico)
  - query:idCliente (integer, optional) - Identificador unico del cliente
  - query:rutCliente (integer, optional) - Rut del cliente (sin guion ni digito verificador)
  - query:idProyecto (integer, optional) - Identificador unico del proyecto

### GET `/clientes/seguimientos`
- operationId: `obtenerSeguimientosPorCliente`
- descripcion: Obtiene los seguimientos por cliente
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - query:tipoCliente (string enum=['NATURAL', 'JURIDICO'], optional) - Tipo de cliente (Natural o Juridico)
  - query:idCliente (integer, optional) - Identificador unico del cliente
  - query:rutCliente (integer, optional) - Rut del cliente (sin guion ni digito verificador)
  - query:idProyecto (integer, optional) - Identificador unico del proyecto
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD

### GET `/clientes/tipos`
- operationId: `obtenerTiposDeCliente`
- descripcion: Obtener tipos de clientes
- seguridad: JWT
- respuesta 200: `object`

### GET `/clientes/vendedor-by-cliente`
- operationId: `findByCliente`
- descripcion: Buscar vendedores por cliente
- seguridad: JWT
- respuesta 200: `#/definitions/VendedorByClienteResponse`
- parametros:
  - query:identificador (string enum=['rut/dni', 'id cliente'], required) - Seleccione nombre del identificador a buscar
  - query:valorIdentificador (integer, required) - Ingresar el rut o el id del cliente, segun lo ingresado en el identificador (Rut sin guion ni digito verificador)
  - query:tipoCliente (string enum=['Natural', 'Juridico'], optional) - Seleccione tipo de cliente
  - query:idProyecto (integer, optional) - Ingresar ID del proyecto

### GET `/clientes/{idCliente}/canales/{idCanal}`
- operationId: `obtenerClienteExternoPorCanal`
- descripcion: Obtiene el cliente externo por canal de pago
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:idCliente (integer, required)
  - path:idCanal (integer, required)

### GET `/clientes/{id}/comparecientes`
- operationId: `obtenerComparecientesPorIdCliente`
- descripcion: Obtiene los comparecientes de un cliente
- seguridad: JWT
- respuesta 200: `array[object]`
- parametros:
  - path:id (number, required)

### POST `/cotizaciones`
- operationId: `agregarCotizaciones`
- descripcion: Agregar Cotizaciones
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - query:secuencial (boolean, optional)
  - query:descuento_producto (boolean, optional)
- body:
  - cotizaciones: #/definitions/Cotizaciones

### GET `/cotizaciones/listar`
- operationId: `findAllCotizacion`
- descripcion: Obtener cotizaciones
- seguridad: JWT
- respuesta 200: `#/definitions/Cotizaciones`
- parametros:
  - query:origen (string enum=['Sistema GCI', 'Web'], optional) - origen
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:pagina (integer, required) - pagina actual

### GET `/cotizaciones/vendedor-by-id-cotizacion`
- operationId: `findByIdCotizacion`
- descripcion: Buscar Vendedor por cotizacion
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - query:idCotizacion (integer, required) - identificador de negocio

### GET `/cotizaciones/{idCotizacion}`
- operationId: `getCotizacionById`
- descripcion: Buscar una cotizacion por su identificador
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:idCotizacion (integer, required) - Identificador de cotizacion

### GET `/cotizaciones/{idCotizacion}/pdf`
- operationId: `getCotizacionPdf`
- descripcion: Entrega el PDF de una cotizacion
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:idCotizacion (integer, required) - Identificador de la cotizacion
  - query:tipoDescarga (integer, optional) - Tipo de descarga

### GET `/cuotas/externas`
- operationId: `obtenerCuotas`
- descripcion: Obtener cuotas externas
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- parametros:
  - query:idClienteInterno (string, optional) - Identificador del cliente en GCI
  - query:idClienteExterno (string, optional) - Identificador del cliente en el canal de pago externo
  - query:idFormaPagoInterna (string, optional) - Identificador de la forma de pago en GCI
  - query:idFormaPagoExterna (string, optional) - Identificador de la forma de pago externa
  - query:idCanal (string, optional) - Identificador del canal
  - query:estadoCuotaInterno (string enum=['Documentada Incompleta', 'Documentada Completa', 'Sin Documentar'], optional) - Estado interno de GCI de la cuota
  - query:estadoCuotaExterno (string, optional) - Estado externo de la cuota

### GET `/cuotas/externo`
- operationId: `getAllCuotasExterno`
- seguridad: JWT
- respuesta 200: `#/definitions/Error`

### POST `/cuotas/externo`
- operationId: `cuotasExterno`
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- body:
  - data: id_cuota (integer), id_externo (string), id_canal (integer), estado_externo (string)

### PUT `/cuotas/externo`
- operationId: `cuotasExternoUpdate`
- seguridad: JWT
- respuesta 200: `no documentado`
- body:
  - data: id_cuota (integer), id_externo (string), id_canal (integer), estado_externo (string)

### POST `/cuotas/externo/intentos`
- operationId: `cuotasExternoIntentos`
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- body:
  - data: id_externo (string), estado (string), id_estado (integer)

### GET `/datos-forma-pago`
- operationId: `findFormaPagoByRutAndIdProyectoAndApiKey`
- descripcion: Endpoint para listar la forma de pago según el rut del usuario
- seguridad: JWT
- respuesta 200: `#/definitions/DatosFormaPago`
- parametros:
  - query:rutDNI (string, required) - Identificador del usuario (RUT/DNI)
  - query:idProyecto (integer, optional) - Identificador del proyecto
  - query:idNegocio (integer, optional) - Identificador del negocio
  - query:idPromesa (integer, optional) - Identificador de la promesa

### GET `/datos-forma-pago-reserva`
- operationId: `findFormaPagoByRutAndIdProyectoAndApiKeyReserva`
- descripcion: Endpoint para listar la forma de pago según el rut del usuario en base a las reservas
- seguridad: JWT
- respuesta 200: `#/definitions/DatosFormaPagoReserva`
- parametros:
  - query:rutDNI (string, required) - Identificador del usuario (RUT/DNI)
  - query:idProyecto (integer, optional) - Identificador del proyecto
  - query:idNegocio (integer, optional) - Identificador del negocio
  - query:idReserva (integer, optional) - Identificador de la reserva

### GET `/documentos`
- operationId: `findAllByRutOrIdCliente`
- descripcion: Obtiene los documentos de un cliente por su ID o rut
- seguridad: JWT
- respuesta 200: `#/definitions/Documentos`
- parametros:
  - query:tipoIdentificador (string enum=['rut', 'id_cliente'], required) - Tipo identificador del cliente asociado a los documentos a buscar (Rut o ID Cliente)
  - query:identificador (string, required) - Valor del identificador del cliente asociado a los documentos a buscar (Ingrese el valor del rut o el id cliente
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:extension (string, optional) - Extension del documento
  - query:idTipoDocumento (string, optional) - ID del tipo de documento
  - query:idDocumento (string, optional) - ID del documento
  - query:idProyecto (string, optional) - ID del proyecto

### POST `/escritura`
- operationId: `crearEscritura`
- descripcion: crear escritura
- seguridad: JWT
- respuesta 200: `#/definitions/Escritura`
- body:
  - escritura: #/definitions/CrearEscrituraRequest

### PUT `/escritura/modificar-fecha-entrega/`
- operationId: `updateFechaEntregaByIdProducto`
- descripcion: Actualizar la fecha de entrega por el id del producto
- seguridad: APIKey
- respuesta 200: `{"description": "Actualización Exitosa"}`
- body:
  - data: deliveryDate (string/date), deliveryHora (string), propertyId (integer), updatedBy (integer), source (string)

### GET `/escrituras`
- operationId: `obtenerEscrituras`
- descripcion: Obtener las ultimas escrituras que se ingresaron en el sistema
- seguridad: JWT
- respuesta 200: `array[object]`
- parametros:
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:asignacionFechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:asignacionFechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD

### GET `/etapa/{id}/productos-secundarios`
- operationId: `findByEtapaId`
- descripcion: Obtener productos secundarios por etapa
- seguridad: JWT
- respuesta 200: `array[#/definitions/ProductoSecundario]`
- parametros:
  - path:id (integer, required) - id Etapa

### GET `/etapas/{id}/subagrupaciones`
- operationId: `findByEtapaId`
- descripcion: Obtener subapgrupaciones por etapa
- seguridad: JWT
- respuesta 200: `#/definitions/SubAgrupaciones`
- parametros:
  - path:id (integer, required) - id de la etapa

### GET `/expectativas`
- operationId: `obtenerExpectativas`
- descripcion: Obtiene todas las expectativas
- seguridad: JWT
- respuesta 200: `array[object]`

### GET `/forma-pago/{idFormaPago}/canales/{idCanal}`
- operationId: `obtenerFormaPagoExternaPorCanal`
- descripcion: Obtiene la forma de pago externa por canal de pago
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:idFormaPago (integer, required)
  - path:idCanal (integer, required)

### POST `/formaPagos/cuotas/{id}/pagos`
- operationId: `pagarCuotas`
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- parametros:
  - path:id (integer, required)
- body:
  - data: pagador (object), codigoTipoDocumento (string), fecha (string), idBanco (integer), sucursal (string), cuentaCorriente (string), serie (string), numero (string), monto (number), comentario (string)

### GET `/formaPagos/externo`
- operationId: `getAllFormasPagoExterno`
- descripcion: permite vincular un idFormaPago de GCI con un Plan de Pago externo.
- seguridad: JWT
- respuesta 200: `#/definitions/Error`

### POST `/formaPagos/externo`
- operationId: `formasPagoExterno`
- descripcion: permite vincular un idFormaPago de GCI con un Plan de Pago externo.
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- body:
  - data: id_forma_pago (integer), id_externo (string), id_canal (integer), estado_externo (string)

### PUT `/formaPagos/externo`
- operationId: `formasPagoExternoUpdate`
- descripcion: permite vincular un idFormaPago de GCI con un Plan de Pago externo.
- seguridad: JWT
- respuesta 200: `no documentado`
- body:
  - data: id_forma_pago (integer), id_externo (string), id_canal (integer), estado_externo (string)

### GET `/gestor/cotizaciones`
- operationId: `findCotizacionesGestor`
- descripcion: Obtener Cotizaciones
- seguridad: JWT
- respuesta 200: `#/definitions/Gestor`
- parametros:
  - query:subagrupaciones (string, optional) - Id de subagrupación
  - query:responsable (string, optional) - Responsable
  - query:tipoIngreso (array, optional) - Tipo de Ingreso
  - query:tipoFecha (string enum=['Fecha de Ingreso', 'Fecha de Vencimiento'], optional) - Tipo de Fecha
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:pagina (integer, required) - pagina actual

### GET `/gestor/leads`
- operationId: `findLeadsGestor`
- descripcion: Obtener Leads
- seguridad: JWT
- respuesta 200: `#/definitions/Gestor`
- parametros:
  - query:subagrupaciones (string, optional) - Id de subagrupación
  - query:responsable (string, optional) - Responsable
  - query:tipoIngreso (array, optional) - Tipo de Ingreso
  - query:tipoFecha (string enum=['Fecha de Ingreso', 'Fecha de Vencimiento'], optional) - Tipo de Fecha
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:pagina (integer, required) - pagina actual

### GET `/gestor/reservas`
- operationId: `findReservasGestor`
- descripcion: Obtener Reservas
- seguridad: JWT
- respuesta 200: `#/definitions/Gestor`
- parametros:
  - query:subagrupaciones (string, optional) - Id de subagrupación
  - query:responsable (string, optional) - Responsable
  - query:tipoIngreso (array, optional) - Tipo de Ingreso
  - query:tipoFecha (string enum=['Fecha de Ingreso', 'Fecha de Vencimiento'], optional) - Tipo de Fecha
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:pagina (integer, required) - pagina actual

### GET `/grados-estudio`
- operationId: `obtenerGradosEstudio`
- descripcion: Obtiene los oficios
- seguridad: JWT
- respuesta 200: `array[object]`

### GET `/inmobiliarias`
- operationId: `findByAPIKey`
- descripcion: Obtener inmobiliaria por apikey
- seguridad: APIKey
- respuesta 200: `#/definitions/Inmobiliaria`

### GET `/leads`
- operationId: `findAllCentralizador`
- descripcion: Obtener registros centralizador
- seguridad: JWT
- respuesta 200: `array[#/definitions/Centralizador]`
- parametros:
  - query:estado (string enum=['Bueno', 'Con Problemas', 'Malo'], optional)
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:contacto (string, optional)
  - query:rutVendedor (string, optional)

### PATCH `/leads`
- operationId: `updateCentralizador`
- descripcion: Actualizar registros centralizador
- seguridad: JWT
- respuesta 200: `string`
- body:
  - data: {"type": "object"}

### POST `/leads`
- operationId: `insertCentralizador`
- descripcion: Insertar registros via API
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- body:
  - data: medio_llegada (string), fecha_ingreso (string/date-time), rut_dni (string), nombre (string), apellido_paterno (string), apellido_materno (string), telefono_fijo (string), telefono_movil (string), email (string), proyecto (string), proyecto_id (integer), producto_id (integer), producto_glosa (string), comentario (string), clausula_publicidad (string), clausula_proyecto (string), utm_source (string), utm_medium (string), utm_campaign (string), utm_term (string), utm_content (string)

### POST `/leads/reasignar-vendedor`
- operationId: `reasignarVendedor`
- descripcion: Reasigna un lead a un nuevo vendedor
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- body:
  - data: idLead (number), rutVendedor (number)

### GET `/leads/vendedores`
- operationId: `obtenerVendedoresAsignables`
- descripcion: Obtener un registro del centralizador por su identificador
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - query:rutVendedor (string, optional) - RUT del vendedor

### GET `/leads/{id}`
- operationId: `getLeadById`
- descripcion: Obtener un registro del centralizador por su identificador
- seguridad: JWT
- respuesta 200: `#/definitions/Centralizador`
- parametros:
  - path:id (number, required)

### PATCH `/leads/{id}/descartar`
- operationId: `descartarLead`
- descripcion: Descartar un lead del centralizador por su identificador
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (number, required)
- body:
  - descartarData: idMotivoDescarte (integer), comentarioDescarte (string)

### POST `/login`
- operationId: `login`
- descripcion: Login
- seguridad: APIKey
- respuesta 200: `#/definitions/LoginResponse`
- body:
  - credentials: #/definitions/Credentials

### GET `/me`
- operationId: `me`
- descripcion: Obtener perfil del usuario autenticado
- seguridad: JWT, SsoJWT
- respuesta 200: `#/definitions/LoginResponse`
- parametros:
  - query:apikey (string, required) - apikey de inmobiliaria
  - query:username (string, optional) - Nombre de usuario o email

### POST `/medio-llegada`
- operationId: `insertMedioLlegada`
- descripcion: Permite crear nuevo medio de llegada a un proyecto
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- body:
  - data: glosa_medio (string), id_proyecto (integer)

### PATCH `/modelos/{id}`
- operationId: `updateModelo`
- descripcion: Actualiza atributos parciales del modelo
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - ID del modelo
- body:
  - data: glosa_modelo (string), cant_dormitorios (integer), cant_banos (number)

### GET `/modelos/{id}/productos-principales`
- operationId: `findByModeloId`
- descripcion: Obtener productos principales por modelo
- seguridad: JWT
- respuesta 200: `array[#/definitions/ProductoPrincipal]`
- parametros:
  - path:id (integer, required) - id del modelo

### GET `/modelos/{modeloId}/imagenes/{id}`
- operationId: `findByModeloIdAndImagenId`
- descripcion: Obtener imagen para de un modelo
- seguridad: JWT
- respuesta 200: `string`
- parametros:
  - path:modeloId (integer, required) - id del modelo
  - path:id (integer, required) - id imagen

### GET `/motivos-descarte`
- operationId: `obtenerMotivosDescarte`
- descripcion: Obtiene todos los motivos de descarte
- seguridad: JWT
- respuesta 200: `array[object]`

### GET `/negocios`
- operationId: `findAll`
- descripcion: Buscar Negocios Activos
- seguridad: JWT
- respuesta 200: `array[#/definitions/Negocio]`
- parametros:
  - query:productos.nombres (array, optional) - nombres de productos principales

### POST `/negocios/{id}/agregar-producto-secundario-o-adicional-promesa`
- operationId: `agregarProductoSecundarioOAdicional`
- descripcion: agregar producto secundario o adicional a promesa
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de negocio
- body:
  - body: idProducto (integer, req), tipoProducto (string enum=['Secundario', 'Adicional'], req), descuento (object, req)

### POST `/negocios/{id}/agregar-producto-secundario-o-adicional-reserva`
- operationId: `agregarProductoSecundarioOAdicionalAReserva`
- descripcion: agregar producto secundario o adicional a reserva
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de negocio
- body:
  - body: #/definitions/AgregarProductoSecundarioOAdicionalAReservaRequest

### POST `/negocios/{id}/eliminar-producto-secundario-o-adicional-promesa`
- operationId: `eliminarProductoSecundarioOAdicional`
- descripcion: eliminar producto secundario o adicional a promesa
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de negocio
- body:
  - body: idProducto (integer, req), tipoProducto (string enum=['Secundario', 'Adicional'], req)

### POST `/negocios/{id}/eliminar-producto-secundario-o-adicional-reserva`
- operationId: `eliminarProductoSecundarioOAdicionalAReserva`
- descripcion: eliminar producto secundario o adicional a reserva
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de negocio
- body:
  - body: #/definitions/EliminarProductoSecundarioOAdicionalAReservaRequest

### POST `/negocios/{id}/modificar-cliente-promesa`
- operationId: `modificarClientePromesa`
- descripcion: Modificar cliente promesa por identificador de negocio
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de negocio
- body:
  - body: rut (integer, req), tipoCompareciente (string enum=['CONYUGE', 'HIJO(A)', 'PADRE/MADRE', 'CODEUDOR', 'CONVIVIENTE', 'OTRO FAMILIAR', 'OTRA RELACION', 'REPRESENTANTE'], req)

### POST `/negocios/{id}/modificar-cliente-reserva`
- operationId: `modificarClienteReserva`
- descripcion: modificar cliente de una reserva
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de negocio
- body:
  - body: rut (integer, req), tipoCompareciente (string enum=['CONYUGE', 'HIJO(A)', 'PADRE/MADRE', 'CODEUDOR', 'CONVIVIENTE', 'OTRO FAMILIAR', 'OTRA RELACION', 'REPRESENTANTE'], req)

### POST `/negocios/{id}/modificar-producto-principal-promesa`
- operationId: `modificarProductoPrincipal`
- descripcion: modificar producto principal a promesa
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de negocio
- body:
  - body: idProducto (integer, req), descuento (object, req)

### POST `/negocios/{id}/modificar-producto-principal-reserva`
- operationId: `modificarProductoPrincipal`
- descripcion: modificar producto principal a reserva
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de negocio
- body:
  - body: #/definitions/ModificarProductoPrincipalAReservaRequest

### POST `/negocios/{id}/modificar-promesa`
- operationId: `modificarPromesa`
- descripcion: Modificar promesa por identificador de negocio
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de negocio
- body:
  - body: fecha (string/date, req)

### GET `/negocios/{id}/promesas`
- operationId: `findByIdNegocio`
- descripcion: Buscar Reserva por identificador de negocio
- seguridad: JWT
- respuesta 200: `#/definitions/PromesaServicio`
- parametros:
  - path:id (integer, required) - identificador de negocio

### GET `/negocios/{id}/reservas`
- operationId: `findByIdNegocio`
- descripcion: Buscar Reserva por identificador de negocio
- seguridad: JWT
- respuesta 200: `#/definitions/Reserva`
- parametros:
  - path:id (integer, required) - identificador de negocio

### POST `/negocios/{id}/reversar-escritura`
- operationId: `reversarEscritura`
- descripcion: reversar escritura de un negocio
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de negocio

### GET `/oficios`
- operationId: `obtenerOficios`
- descripcion: Obtiene los oficios
- seguridad: JWT
- respuesta 200: `array[object]`

### GET `/packs`
- operationId: `findAll`
- descripcion: Obtener packs
- seguridad: JWT
- respuesta 200: `array[#/definitions/Pack]`
- parametros:
  - query:id_proyecto (integer, optional) - id proyecto
  - query:id_modelo (integer, optional) - id modelo
  - query:id_producto (integer, optional) - id producto
  - query:id_producto_secundario (integer, optional) - id producto secundario

### POST `/packs`
- operationId: `insertPack`
- descripcion: Insertar pack
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- body:
  - data: productoPrincipalId (integer), productosSecundariosIds (string), productosAdicionalesIds (string)

### DELETE `/packs/{id}`
- operationId: `deletePack`
- descripcion: Eliminar pack por ID
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - ID del pack a eliminar

### PUT `/packs/{id}`
- operationId: `updatePack`
- descripcion: Actualizar pack
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - ID del pack a actualizar
- body:
  - data: productoPrincipalId (integer), productosSecundariosIds (string), productosAdicionalesIds (string)

### GET `/pagos/conciliados`
- operationId: `obtenerPagosConciliados`
- descripcion: Endpoint para obtener los ultimos pagos que se conciliaron en el sistema
- seguridad: JWT
- respuesta 200: `#/definitions/Pago`
- parametros:
  - query:fechaInicio (string/date, optional) - Fecha inicial desde la cual se desea filtrar (Referencia a la fecha de edicion del pago)
  - query:fechaFin (string/date, optional) - Fecha final desde la cual se desea filtrar (Referencia a la fecha de edicion del pago)
  - query:idNegocio (integer, optional) - Identificador del negocio
  - query:idPromesa (integer, optional) - Identificador de la promesa
  - query:idReserva (integer, optional) - Identificador de la reserva
  - query:idCliente (integer, optional) - Identificador del cliente
  - query:idProyecto (integer, optional) - Identificador del proyecto
  - query:idCuota (integer, optional) - Identificador de la cuota
  - query:idPago (integer, optional) - Identificador del pago

### GET `/pagos/ingresados`
- operationId: `obtenerPagosIngresados`
- descripcion: Endpoint para obtener los ultimos pagos que se ingresaron en el sistema
- seguridad: JWT
- respuesta 200: `#/definitions/Pago`
- parametros:
  - query:fechaInicio (string/date, optional) - Fecha inicial desde la cual se desea filtrar (Referencia a la fecha de creacion del pago)
  - query:fechaFin (string/date, optional) - Fecha final desde la cual se desea filtrar (Referencia a la fecha de creacion del pago)
  - query:idNegocio (integer, optional) - Identificador del negocio
  - query:idPromesa (integer, optional) - Identificador de la promesa
  - query:idReserva (integer, optional) - Identificador de la reserva
  - query:idCliente (integer, optional) - Identificador del cliente
  - query:idProyecto (integer, optional) - Identificador del proyecto
  - query:idCuota (integer, optional) - Identificador de la cuota
  - query:idPago (integer, optional) - Identificador del pago

### GET `/pagos/reversados`
- operationId: `obtenerPagosReversados`
- descripcion: Endpoint para obtener los ultimos pagos que se reversaron en el sistema
- seguridad: JWT
- respuesta 200: `#/definitions/Pago`
- parametros:
  - query:fechaInicio (string/date, optional) - Fecha inicial desde la cual se desea filtrar (Referencia a la fecha de edicion del pago)
  - query:fechaFin (string/date, optional) - Fecha final desde la cual se desea filtrar (Referencia a la fecha de edicion del pago)
  - query:idNegocio (integer, optional) - Identificador del negocio
  - query:idPromesa (integer, optional) - Identificador de la promesa
  - query:idReserva (integer, optional) - Identificador de la reserva
  - query:idCliente (integer, optional) - Identificador del cliente
  - query:idProyecto (integer, optional) - Identificador del proyecto
  - query:idCuota (integer, optional) - Identificador de la cuota
  - query:idPago (integer, optional) - Identificador del pago

### POST `/pagos/{idPago}/conciliaciones`
- operationId: `conciliaciones`
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- parametros:
  - path:idPago (integer, required)
- body:
  - data: fecha (string/date-time)

### POST `/pagos/{idPago}/depositos`
- operationId: `depositos`
- seguridad: JWT
- respuesta 200: `#/definitions/Error`
- parametros:
  - path:idPago (integer, required)
- body:
  - data: fecha (string/date-time), id_cta_cte (integer)

### GET `/paises`
- operationId: `obtenerPaises`
- descripcion: Obtener paises
- seguridad: JWT
- respuesta 200: `#/definitions/Comuna`

### PATCH `/producto-principal/{id}`
- operationId: `actualizarDatos`
- descripcion: Actualizar un producto principal
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - ID del producto principal
- body:
  - body: #/definitions/ProductoPrincipalUpdate

### POST `/producto-principal/{id}/bloquear`
- operationId: `bloquear`
- descripcion: bloquear porducto principal
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de producto

### POST `/producto-principal/{id}/desbloquear`
- operationId: `desbloquear`
- descripcion: desbloquear porducto principal
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de producto

### PATCH `/producto-secundario/precios`
- operationId: `actualizarPreciosDeProductoSecundario`
- descripcion: Actualizar precios de un producto secundario
- seguridad: JWT
- respuesta 200: `object`
- body:
  - ActualizarPrecios: productosSecundarios (array)

### POST `/producto-secundario/{id}/bloquear`
- operationId: `bloquearProductoSecundario`
- descripcion: bloquear porducto secundario
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de producto

### POST `/producto-secundario/{id}/desbloquear`
- operationId: `desbloquearProductoSecundario`
- descripcion: desbloquear porducto principal
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de producto

### GET `/productos-principales`
- operationId: `findAllByUsuario`
- descripcion: Buscar Productos Principales
- seguridad: JWT
- respuesta 200: `array[#/definitions/ProductoPrincipal]`
- parametros:
  - query:nombres (array, optional)

### PATCH `/productos-principales/atributos`
- operationId: `actualizarAtributosDeProductoPrincipales`
- descripcion: Actualizar atributos de uno o varios productos principales
- seguridad: JWT
- respuesta 200: `object`
- body:
  - ActualizarAtributos: productosPrincipales (array)

### PATCH `/productos-principales/precios`
- operationId: `actualizarPreciosDeProductoPrincipales`
- descripcion: Actualizar precios de uno o varios productos principales
- seguridad: JWT
- respuesta 200: `object`
- body:
  - ActualizarPrecios: productosPrincipales (array)

### PATCH `/productos-secundarios/{id}`
- operationId: `actualizarProductoSecundario`
- descripcion: Permite actualizar todos los atributos de 'precio', 'superficie_total', 'superficie_municipal_final','piso'.
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - identificador de producto
- body:
  - body: precio (number), piso (string), superficie_total (number), superficie_municipal_final (number)

### GET `/profesiones`
- operationId: `obtenerProfesiones`
- descripcion: Obtiene las profesiones
- seguridad: JWT
- respuesta 200: `array[object]`

### POST `/promesa`
- operationId: `crearPromesa`
- descripcion: crear promesa
- seguridad: JWT
- respuesta 200: `#/definitions/Promesa`
- body:
  - promesa: #/definitions/CrearPromesaRequest

### GET `/promesas`
- operationId: `findAllPromesasByFechas`
- descripcion: Buscar promesas por fecha de firma
- seguridad: JWT
- respuesta 200: `array[#/definitions/PromesaServicio]`
- parametros:
  - query:fechaInicioFirmaClientePromesa (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFinFirmaClientePromesa (string/date, optional) - Ingresar en formato YYYY-MM-DD

### GET `/promesas/canceladas`
- operationId: `findPromesasCanceladasByFechas`
- descripcion: Listado de promesas ordenadas por la fecha-cancelacion
- seguridad: JWT
- respuesta 200: `array[object]`
- parametros:
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD

### POST `/promesas/cancelar`
- operationId: `cancelarPromesa`
- descripcion: cancelar promesa a partir de su identificador de negocio
- seguridad: JWT
- respuesta 200: `object`
- body:
  - promesa: idNegocio (integer), idTipoCancelacion (integer, req), descripcion (string, req)

### GET `/promesas/fecha-firma-comprador`
- operationId: `findFirmaCompradorByFechas`
- descripcion: Listado de promesas ordenadas por la fecha-firma-comprador
- seguridad: JWT
- respuesta 200: `array[object]`
- parametros:
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD

### GET `/promesas/fecha-inmobiliaria`
- operationId: `findFirmaInmobiliariaByFechas`
- descripcion: Listado de promesas ordenadas por la fecha-inmobiliaria 
- seguridad: JWT
- respuesta 200: `array[object]`
- parametros:
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaInmobiliariaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaInmobiliariaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD

### GET `/promesas/{idPromesa}`
- operationId: `findByIdPromesa`
- descripcion: Buscar Promesa por identificador de la promesa
- seguridad: JWT
- respuesta 200: `#/definitions/PromesaServicio`
- parametros:
  - path:idPromesa (integer, required) - Identificador de la promesa

### GET `/proyectos`
- operationId: `findByUsuario`
- descripcion: Obtener proyectos por usuario
- seguridad: JWT
- respuesta 200: `#/definitions/Proyectos`

### GET `/proyectos/nombre/{nombre}/reservas`
- operationId: `findByNombreProyecto`
- descripcion: Obtener reservas por proyecto
- seguridad: JWT
- respuesta 200: `array[#/definitions/Reserva]`
- parametros:
  - path:nombre (string, required) - nombre del proyecto

### GET `/proyectos/{idProyecto}/clientes/{idCliente}/mediosDeLlegada`
- operationId: `obtenerMedioLlegadaPorCliente`
- descripcion: Obtiene todos los medios de llegada asociados a un cliente de un proyecto
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:idProyecto (integer, required)
  - path:idCliente (integer, required)

### GET `/proyectos/{id}/cuentas-corrientes`
- operationId: `getCuentasCorrientes`
- descripcion: Obtener Cuentas Corrientes por Proyecto
- seguridad: JWT
- respuesta 200: `no documentado`
- parametros:
  - path:id (integer, required) - id del proyecto

### GET `/proyectos/{id}/etapas`
- operationId: `findByProyectoId`
- descripcion: Obtener etapas por proyecto
- seguridad: JWT
- respuesta 200: `#/definitions/Etapas`
- parametros:
  - path:id (integer, required) - id del proyecto

### GET `/proyectos/{id}/mediosDeLlegada`
- operationId: `findByProyectoId`
- descripcion: Obtener medios de llegada por proyecto
- seguridad: JWT
- respuesta 200: `#/definitions/MediosDeLlegada`
- parametros:
  - path:id (integer, required) - id del proyecto

### GET `/proyectos/{id}/modelos`
- operationId: `findByProyectoId`
- descripcion: Obtener modelos por proyecto
- seguridad: JWT
- respuesta 200: `#/definitions/Modelos`
- parametros:
  - path:id (integer, required) - id del proyecto

### GET `/proyectos/{id}/productos-adicionales`
- operationId: `findByProyectoId`
- descripcion: Obtener productos adicionales por proyecto
- seguridad: JWT
- respuesta 200: `array[#/definitions/ProductoAdicional]`
- parametros:
  - path:id (integer, required) - id del proyecto

### GET `/proyectos/{id}/productos-principales`
- operationId: `findByProyectoId`
- descripcion: Obtener productos principales por proyecto
- seguridad: JWT
- respuesta 200: `array[#/definitions/ProductoPrincipal]`
- parametros:
  - path:id (integer, required) - id del proyecto
  - query:piso (integer, optional) - Piso del producto
  - query:orientacion (string, optional) - orientacion del producto
  - query:tipoProducto (string, optional) - tipo de producto
  - query:programa (string, optional) - programa de producto
  - query:nombreModelo (string, optional) - Nombre del modelo
  - query:disponibleWeb (boolean, optional) - Disponibilidad en web del producto
  - query:disponibleSistema (boolean, optional) - Disponibilidad en sistema del producto
  - query:estado (string enum=['Disponible', 'Reservado', 'Promesado', 'Escriturado'], optional) - Estado del producto

### GET `/proyectos/{id}/productos-secundarios`
- operationId: `findByProyectoId`
- descripcion: Obtener productos secundarios por proyecto
- seguridad: JWT
- respuesta 200: `array[#/definitions/ProductoSecundario]`
- parametros:
  - path:id (integer, required) - id del proyecto

### POST `/refresh-token`
- operationId: `refresh`
- descripcion: Refresh Token
- seguridad: APIKey
- respuesta 200: `object`
- body:
  - body: refresh_token (string, req)

### GET `/reservas`
- operationId: `findAllReservas`
- descripcion: Listado de reservas ordenadas descendentemente su por identificador
- seguridad: JWT
- respuesta 200: `array[object]`
- parametros:
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaAprobacionInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaAprobacionFin (string/date, optional) - Ingresar en formato YYYY-MM-DD

### GET `/reservas/canceladas`
- operationId: `obtenerReservasCanceladas`
- descripcion: Listado de reservas canceladas
- seguridad: JWT
- respuesta 200: `array[object]`
- parametros:
  - query:fechaInicio (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaFin (string/date, optional) - Ingresar en formato YYYY-MM-DD

### POST `/reservas/cancelar`
- operationId: `cancelarReservaByNegocioId`
- descripcion: cancelar reserva
- seguridad: JWT
- respuesta 200: `object`
- body:
  - cancelar: #/definitions/CancelarReservaRequest

### POST `/reservas/modificar`
- operationId: `modificarReserva`
- descripcion: modificar reserva por identificador de negocio
- seguridad: JWT
- respuesta 200: `object`
- body:
  - reserva: idNegocio (integer, req), fecha (string/date, req)

### POST `/reservas/sin-cuotas`
- operationId: `crearReserva`
- descripcion: Crear reserva
- seguridad: JWT
- respuesta 200: `object`
- body:
  - reservaData: idCotizacion (number), fechaReserva (string/date-time)

### GET `/reservas/{id}`
- operationId: `findReservaById`
- descripcion: Buscar Reserva por su identificador unico
- seguridad: JWT
- respuesta 200: `#/definitions/Reserva`
- parametros:
  - path:id (integer, required) - identificador de la reserva

### GET `/seguimientos`
- operationId: `getSeguimientos`
- descripcion: Buscar Seguimientos
- seguridad: JWT
- respuesta 200: `array[object]`
- parametros:
  - query:fechaDesde (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaHasta (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:identificador (string enum=['rut/dni', 'id cliente'], optional) - Seleccione nombre del identificador a buscar
  - query:valorIdentificador (integer, optional) - Ingresar el rut o el id del cliente, segun lo ingresado en el identificador (Rut sin guion ni digito verificador)
  - query:idProyecto (integer, optional) - Ingresar ID del proyecto
  - query:ejecutivo (string, optional) - Ingresar rut del ejecutivo (Rut sin guion ni digito verificador)

### POST `/seguimientos`
- operationId: `CrearSeguimiento`
- descripcion: Crear seguimientos
- seguridad: JWT
- respuesta 200: `array[integer]`
- parametros:
  - query:vendedorSecuencial (boolean, optional) - Asigna vendedor secuencial si no encuentra ultimo vendedor
  - query:mantieneAgendaVendedor (boolean, optional) - Mantiene la fecha del recontacto del vendedor
- body:
  - CrearSeguimiento: #/definitions/CrearSeguimiento

### PATCH `/seguimientos/abortar/{id}`
- operationId: `AbortarSeguimiento`
- descripcion: Abortar seguimientos
- seguridad: JWT
- respuesta 200: `#/definitions/Reserva`
- parametros:
  - path:id (integer, required) - identificador de la evaluación
- body:
  - descartarData: idMotivoDescarte (integer), comentarioDescarte (string)

### GET `/subagrupaciones/{id}/descuentos-sala-ventas`
- operationId: `findBySubAgrupacionId`
- descripcion: Obtener productos adicionales por subAgrupacion
- seguridad: JWT
- respuesta 200: `#/definitions/DescuentoSalaVentas`
- parametros:
  - path:id (integer, required) - id subagrupacion

### GET `/subagrupaciones/{id}/productos-adicionales`
- operationId: `findBySubAgrupacionId`
- descripcion: Obtener productos adicionales por subAgrupacion
- seguridad: JWT
- respuesta 200: `#/definitions/ProductoAdicional`
- parametros:
  - path:id (integer, required) - id subagrupacion

### GET `/subagrupaciones/{id}/productos-secundarios`
- operationId: `findBySubAgrupacionId`
- descripcion: Obtener productos secundarios por subAgrupacion
- seguridad: JWT
- respuesta 200: `#/definitions/ProductoSecundario`
- parametros:
  - path:id (integer, required) - id subagrupacion

### GET `/tipo-contacto/{glosa}`
- operationId: `obtenerTipoContactoPorGlosa`
- descripcion: Obtener el id del contacto por glosa
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:glosa (string, required) - Glosa Tipo Contacto

### GET `/tipoContactos`
- operationId: `obtenerTipoContactos`
- descripcion: Obtiene todos los tipos de contactos
- seguridad: JWT
- respuesta 200: `array[object]`

### GET `/tipoDocumentos`
- operationId: `getTipoDocs`
- descripcion: Obtener tipos de documentos
- seguridad: JWT
- respuesta 200: `array[#/definitions/TipoDocumento]`

### GET `/v2/ciudades/{id}/comunas`
- operationId: `findComunasByIdCiudadV2`
- descripcion: Obtener comunas por ciudad
- seguridad: JWT
- respuesta 200: `#/definitions/Comuna`
- parametros:
  - path:id (number, required) - Identificador unico de la ciudad

### GET `/v2/negocios`
- operationId: `findAll`
- descripcion: Buscar Negocios Activos Paginados
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - query:page (integer, required) - pagina actual
  - query:productos.nombres (array, optional) - nombres de productos principales

### GET `/v2/regiones`
- operationId: `findRegionV2`
- descripcion: Obtener Regiones
- seguridad: JWT
- respuesta 200: `#/definitions/Region`

### GET `/v2/regiones/{id}/ciudades`
- operationId: `findCiudadByIdRegionV2`
- descripcion: Obtener ciudades por Región
- seguridad: JWT
- respuesta 200: `array[#/definitions/Ciudad]`
- parametros:
  - path:id (number, required) - Identificador unico de la Región

### GET `/v2/reservas/canceladas`
- operationId: `obtenerReservasCanceladasV2`
- descripcion: Buscar Reservas canceladas
- seguridad: JWT
- respuesta 200: `array[object]`
- parametros:
  - query:fechaCancelacionDesde (string/date, optional) - Ingresar en formato YYYY-MM-DD
  - query:fechaCancelacionHasta (string/date, optional) - Ingresar en formato YYYY-MM-DD

## integracion

### GET `/entity-mappings`
- operationId: `getMappings`
- descripcion: Lista de homologaciones
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - query:pagina (integer, optional) - Número de página a obtener
  - query:source_system (string, optional) - Filtrar por sistema origen
  - query:internal_entity (string, optional) - Filtrar por entidad interna
  - query:external_entity (string, optional) - Filtrar por entidad externa
  - query:internal_id (string, optional) - Filtrar por ID interno
  - query:external_id (string, optional) - Filtrar por ID externo

### POST `/entity-mappings`
- operationId: `createEntityMapping`
- descripcion: Crea una homologación
- seguridad: JWT
- respuesta 200: `no documentado`
- body:
  - body: source_system (string), external_entity (string), internal_entity (string), internal_id (string), external_id (string)

### DELETE `/entity-mappings/{id}`
- operationId: `deleteEntityMapping`
- descripcion: Elimina una homologación
- seguridad: JWT
- respuesta 200: `no documentado`
- parametros:
  - path:id (integer, required) - ID del homologación a eliminar

### PATCH `/entity-mappings/{id}`
- operationId: `patchEntityMapping`
- descripcion: Actualiza parcialmente una homologación
- seguridad: JWT
- respuesta 200: `object`
- parametros:
  - path:id (integer, required) - ID del homologación a actualizar
- body:
  - body: source_system (string), external_entity (string), internal_entity (string), internal_id (string), external_id (string)
