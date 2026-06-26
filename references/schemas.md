# Schemas / definitions de PlanOK API

Definitions documentadas: 110

Consultar `openapi.json` para objetos completos y anidaciones. Este archivo resume propiedades principales.

## AgendaContacto
- type: `array`

## AgregarProductoSecundarioOAdicionalAReservaRequest
- type: `object`
- propiedades:
  - `idProducto`: integer (required)
  - `tipoProducto`: string (required; enum=['Secundario', 'Adicional'])
  - `descuento`: #/definitions/Descuento (required)

## Archivos
- type: `array`

## ArrayOfGlosas
- type: `array`

## BackgroundReserva
- type: `object`
- propiedades:
  - `imagenFondo`: string

## Banners
- type: `array`

## CancelarReservaRequest
- type: `object`
- propiedades:
  - `idNegocio`: integer
  - `idTipoCancelacion`: integer
  - `motivoCancelacion`: string
  - `fecha`: string/date-time

## CanonicalUserProjection
- type: `object`
- propiedades:
  - `email`: string/email (required)
  - `firstName`: string (required)
  - `lastName`: string (required)
  - `documentId`: string (required)
  - `tenant_slug`: string (required)
  - `isActive`: boolean (required)

## Centralizador
- type: `object`
- propiedades:
  - `id`: integer
  - `estado`: string
  - `fecha_ingreso`: string/date-time
  - `fecha_contacto`: string/date-time
  - `via_ingreso`: string
  - `id_medio_llegada`: integer
  - `medio_llegada`: string
  - `medio_defecto`: string
  - `rut`: string
  - `nombre`: string
  - `telefojo_fijo`: string
  - `telefono_movil`: string
  - `email`: string
  - `proyecto`: string
  - `producto`: string/email
  - `vendedor`: string
  - `correo_vendedor`: string
  - `contacto`: string
  - `comentario`: string
  - `id_cliente`: integer
  - `id_proyecto`: integer
  - `id_visita`: integer
  - `rut_usuario`: string
  - `clausula_publicidad`: string
  - `clausula_proyecto`: string
  - `utm_source`: string
  - `utm_medium`: string
  - `utm_campaign`: string
  - `utm_term`: string
  - `utm_content`: string

## Ciudad
- type: `object`
- propiedades:
  - `idRegion`: integer
  - `glosaRegion`: string
  - `numRomano`: string
  - `idCiudad`: integer
  - `glosaCiudad`: string

## ClienteJuridico
- type: `object`
- propiedades:
  - `id`: integer
  - `ruc`: string
  - `razonSocial`: string
  - `giro`: string
  - `departamento`: string
  - `provincia`: string
  - `distrito`: string
  - `telefono`: string
  - `email`: string/email
  - `fechaCreacion`: string/date-time
  - `fechaEdicion`: string/date-time
  - `direccion`: string
  - `representanteLegal`: object

## ClienteJuridicoActualizarRequest
- type: `object`
- propiedades:
  - `identificadorPersonal`: integer (required)
  - `digitoVerificador`: string (required)
  - `razonSocial`: string (required)
  - `telefono`: string (required)
  - `email`: string/email (required)

## ClienteNatural
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `apellidoPaterno`: string
  - `apellidoMaterno`: string
  - `edad`: integer
  - `departamento`: string
  - `provincia`: string
  - `distrito`: string
  - `celular`: string
  - `telefono`: string
  - `email`: string/email
  - `estadoCivil`: string
  - `ingresoFamiliar`: number
  - `ingresoIndividual`: number
  - `profesion`: string
  - `actividadCargo`: string
  - `fechaCreacion`: string/date-time
  - `fechaEdicion`: string/date-time
  - `fechaNacimiento`: string/date-time
  - `genero`: string
  - `dni`: string
  - `direccion`: string
  - `conyuge`: object
  - `paisNacionalidad`: object
  - `rentaLiquida`: string
  - `paisResidencia`: string
  - `origenFondos`: string
  - `esPep`: string
  - `pepInstitucion`: string
  - `pepCargo`: string
  - `pepEsRelacionado`: string
  - `pepRelacionadoTipoRelacion`: string
  - `pepRelacionadoInstitucion`: string
  - `pepRelacionadoCargo`: string
  - `pepRelacionadoRut`: string
  - `pepRelacionadoNombre`: string
  - `pepRelacionadoApellidoPaterno`: string
  - `pepRelacionadoApellidoMaterno`: string

## ClienteNaturalRequest
- type: `object`
- propiedades:
  - `identificadorPersonal`: integer (required)
  - `digitoVerificador`: string (required)
  - `nombre`: string (required)
  - `apellidoPaterno`: string (required)
  - `apellidoMaterno`: string (required)
  - `celular`: string (required)
  - `email`: string/email (required)

## ClienteNaturalRequestReservaEnlinea
- type: `object`
- propiedades:
  - `nombre`: string (required)
  - `apellidoPaterno`: string (required)
  - `apellidoMaterno`: string (required)
  - `email`: string/email (required)
  - `celular`: string (required)
  - `fechaNacimiento`: string (required)
  - `direccion`: string (required)
  - `numero`: integer (required)
  - `deptoCasa`: string (required)
  - `idRegion`: integer
  - `idComuna`: integer

## ClienteNaturalRequestSeparacionEnlinea
- type: `object`
- propiedades:
  - `nombre`: string (required)
  - `apellidoPaterno`: string (required)
  - `apellidoMaterno`: string (required)
  - `email`: string/email (required)
  - `celular`: string (required)
  - `fechaNacimiento`: string (required)
  - `direccion`: string (required)
  - `numero`: integer (required)
  - `deptoCasa`: string (required)
  - `idDepartamento`: integer
  - `idProvincia`: integer
  - `idDistrito`: integer

## ClientesNaturalesCotizaciones
- type: `object`
- propiedades:
  - `id`: integer
  - `productoPrincipal`: object
  - `productoSecundario`: array[object]
  - `ps`: array[object]
  - `utm`: object

## Cobro
- type: `object`
- propiedades:
  - `codigoComerciante`: string (required)
  - `codigoCobro`: string (required)
  - `numero`: string (required)
  - `moneda`: string (required)
  - `montoCobro`: number (required)
  - `fechaEmision`: string/date-time
  - `detalleCobro`: string (required)
  - `cliente`: object (required)

## Comuna
- type: `object`
- propiedades:
  - `idRegion`: integer
  - `glosaRegion`: string
  - `numRomano`: string
  - `idCiudad`: integer
  - `glosaCiudad`: string
  - `idComuna`: integer
  - `glosaComuna`: string

## Contacto
- type: `object`
- propiedades:
  - `nombreCliente`: string
  - `status`: string
  - `estadoContacto`: string
  - `ultimaInteraccion`: string
  - `rut`: string
  - `telefono`: string
  - `correoElectronico`: string
  - `medioLlegada`: string
  - `glosaProyecto`: string
  - `expectativa`: string
  - `tipoContacto`: string
  - `fechaVencimiento`: string
  - `interacciones`: integer
  - `responsable`: string
  - `creacionFichaCliente`: string
  - `fechaUltimoContacto`: string
  - `ingresoIndividual`: string
  - `estado`: string
  - `estadoSeguimiento`: string
  - `ultimoMedioLlegada`: string

## Cotizacion
- type: `object`
- propiedades:
  - `productoPrincipal`: #/definitions/ProductoCotizacion (required)
  - `productosSecundarios`: array[#/definitions/ProductoCotizacion]
  - `productosAdicionales`: array[#/definitions/ProductoCotizacion]
  - `idCliente`: integer (required)
  - `idTipoIVA`: integer (required; enum=[1, 2])
  - `fecha`: string/date-time (required)
  - `idMedioLlegada`: integer (required)
  - `telefonoValidado`: boolean (required)
  - `codigo_cupon`: string
  - `evaluacion`: object (required)
  - `utm`: object
  - `gclid`: string

## CotizacionResponse
- type: `array`

## Cotizaciones
- type: `array`

## CrearEscrituraRequest
- type: `object`
- propiedades:
  - `idNegocio`: integer (required)
  - `fechaEscritura`: string/date (required)

## CrearPromesaRequest
- type: `object`
- propiedades:
  - `idNegocio`: integer (required)
  - `fechaCreacion`: string/date-time (required)
  - `fechaFirmaCliente`: string/date (required)
  - `fechaFirmaInmobiliaria`: string/date (required)

## CrearSeguimiento
- type: `object`
- propiedades:
  - `id_cliente`: integer
  - `id_proyecto`: integer
  - `id_medio`: integer
  - `id_tipo_contacto`: integer
  - `id_expectativa`: integer
  - `comentario`: string
  - `fecha_recontacto`: string/date-time
  - `hora_recontacto`: string - HH:mm
  - `mantenerEjecutivo`: boolean
  - `rut_ejecutivo`: integer
  - `id_tipo`: integer

## Credentials
- type: `object`
- propiedades:
  - `username`: integer (required)
  - `password`: string (required)

## CredentialsKey
- type: `object`
- propiedades:
  - `idCotizacion`: integer (required)

## DatosConfiguracionReserva
- type: `object`
- propiedades:
  - `valorReserva`: number/float
  - `valorReservaTipo`: string
  - `reservaAbonable`: integer
  - `reservaAbonablePie`: integer
  - `reservaGarantiaDepositable`: integer
  - `duracionOferta`: integer

## DatosCotizacion
- type: `object`
- propiedades:
  - `id`: integer
  - `fechaCreacion`: string/date-time
  - `idProducto`: integer
  - `glosaProducto`: string
  - `precioProducto`: number/float
  - `precioListaProducto`: number/float
  - `precioProductosPrincipales`: number/float
  - `descPorcentProducto`: number/float
  - `descUfProducto`: number/float
  - `totalPrecioLista`: number/float
  - `descUfPrecioLista`: number/float
  - `descPorcentPrecioLista`: number/float
  - `subtotal`: number/float
  - `total`: number/float
  - `natRut`: integer
  - `natDvRut`: integer
  - `natNombre`: string
  - `natApellido1`: string
  - `natApellido2`: string
  - `natDirCalle`: string
  - `natDirNumero`: string
  - `natDirDepto`: string
  - `natDirComuna`: string

## DatosDeuda
- type: `object`
- propiedades:
  - `codigoComercio`: integer
  - `deuda`: integer
  - `fechaPago`: string/date-time
  - `detalle`: string
  - `codigoInterno`: string
  - `cliente`: array[object]

## DatosFormaPago
- type: `object`
- propiedades:
  - `negocios`: array[object]

## DatosFormaPagoReserva
- type: `object`
- propiedades:
  - `negocios`: array[object]

## DatosPropiedad
- type: `object`
- propiedades:
  - `inmobiliaria`: object
  - `proyecto`: object
  - `negocios`: array[object]

## Departamento
- type: `object`
- propiedades:
  - `idDepartamento`: integer
  - `glosaDepartamento`: string

## Descuento
- type: `object`
- propiedades:
  - `valor`: number/float
  - `unidad`: string (enum=['%', 'UF'])

## DescuentoSalaVentas
- type: `object`
- propiedades:
  - `id`: integer
  - `codigo`: string
  - `nombre`: string
  - `tipo`: string
  - `monto`: number
  - `fechaInicio`: string/date
  - `fechaFin`: string/date
  - `estado`: boolean
  - `aplica`: string
  - `usuarios`: array[object]
  - `productos`: array[object]
  - `modelos`: array[object]

## DetalleCotizacion
- type: `object`
- propiedades:
  - `id_cotizacion`: integer
  - `id_visita`: integer
  - `id_producto`: integer
  - `precio_producto`: number/float

## DeudaMall
- type: `object`
- propiedades:
  - `codigoComercio`: string (required)
  - `codigoDeuda`: string (required)
  - `numero`: string (required)
  - `montoDeuda`: integer (required)
  - `fechaEmision`: string
  - `detalleDeuda`: string (required)
  - `cliente`: object

## Distrito
- type: `object`
- propiedades:
  - `idDepartamento`: integer
  - `glosaDepartamento`: string
  - `idProvincia`: integer
  - `glosaProvincia`: string
  - `idDistrito`: integer
  - `glosaDistrito`: string

## DocumentarCuotasNegocio
- type: `object`
- propiedades:
  - `codigoTipoCuota`: string (required)
  - `montoPesos`: integer (required)
  - `tipoDocumento`: string (required)
  - `rutCliente`: string (required)

## Documento
- type: `object`
- propiedades:
  - `id`: integer
  - `nombreCorto`: string
  - `nombreLargo`: string
  - `extension`: string
  - `tipoDocumento`: #/definitions/TipoDocumento
  - `urlPublica`: string

## Documentos
- type: `array`

## EliminarProductoSecundarioOAdicionalAReservaRequest
- type: `object`
- propiedades:
  - `idProducto`: integer (required)
  - `tipoProducto`: string (required; enum=['Secundario', 'Adicional'])

## EntityMapping
- type: `object`
- propiedades:
  - `id`: integer - ID único del entity mapping
  - `source_system`: string - Sistema origen del mapping
  - `internal_entity`: string - Nombre de la entidad interna
  - `external_entity`: string - Nombre de la entidad externa
  - `internal_id`: string - ID de la entidad interna
  - `external_id`: string - ID de la entidad externa
  - `created_at`: string/date-time - Fecha y hora de creación
  - `updated_at`: string/date-time - Fecha y hora de última actualización

## EnviarCorreoBienvenida
- type: `object`
- propiedades:
  - `rutDNI`: string
  - `idProyecto`: integer
  - `apiKey`: string

## Error
- type: `object`
- propiedades:
  - `message`: string

## Escritura
- type: `object`
- propiedades:
  - `id`: integer

## EstadoProducto
- type: `object`
- propiedades:
  - `id`: integer
  - `activoWeb`: string
  - `activoSistema`: string
  - `estado`: string

## Etapa
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `notasCotizacion`: #/definitions/NotasCotizacion
  - `validezCotizacion`: integer
  - `telefono`: string
  - `email`: string/email
  - `fechaCreacion`: string/date-time
  - `fechaEdicion`: string/date-time
  - `valorReservaTipo`: string
  - `codigo`: string
  - `fechaInicioVentas`: string/date-time

## Etapas
- type: `array`

## Fotos
- type: `array`

## Gestor
- type: `object`
- propiedades:
  - `paginacion`: object
  - `contactos`: array[#/definitions/Contacto]

## Glosa
- type: `object`
- propiedades:
  - `id`: integer
  - `glosa`: string

## HistorialConsulta
- type: `array`

## HistorialNotificacionesPush
- type: `array`

## IdYNombre
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string

## Imagen
- type: `object`
- propiedades:
  - `url`: string/hostname
  - `orden`: integer

## Imagenes
- type: `array`

## Inmobiliaria
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `nombreInmobiliaria`: string
  - `tipoMoneda`: string
  - `telefono`: string
  - `mail`: string
  - `path`: string
  - `apiEnviaMail`: integer
  - `emailNotificacionApi`: integer
  - `lenguaje`: string
  - `expectativas`: #/definitions/ArrayOfGlosas
  - `razonesDeCompra`: #/definitions/ArrayOfGlosas
  - `canalesADistancia`: #/definitions/ArrayOfGlosas
  - `activaSSO`: boolean
  - `ssoUrl`: string
  - `ssoRealm`: string

## LoginCognito
- type: `object`
- propiedades:
  - `email`: string
  - `password`: string

## LoginResponse
- type: `object`
- propiedades:
  - `token`: string
  - `usuario`: #/definitions/Usuario
  - `idInmobiliaria`: integer

## LoginResponseKey
- type: `object`
- propiedades:
  - `token`: string
  - `idInmobiliaria`: integer

## MedioDeLlegada
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `activo`: boolean
  - `fechaInicio`: string/date
  - `fechaFin`: string/date
  - `fechaCreacion`: string/date-time

## MediosDeLlegada
- type: `array`

## Modelo
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `dormitorios`: integer
  - `banos`: number/float
  - `imagenes`: array[string]
  - `precioDesde`: integer
  - `fechaCreacion`: string/date-time
  - `fechaEdicion`: string/date-time

## Modelos
- type: `array`

## ModificarProductoPrincipalAReservaRequest
- type: `object`
- propiedades:
  - `idProducto`: integer (required)
  - `descuento`: #/definitions/Descuento (required)

## Negocio
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `nombreProductoPrincipal`: string

## NotaCotizacion
- type: `object`
- propiedades:
  - `descripcion`: string
  - `orden`: integer

## NotasCotizacion
- type: `array`

## NotificacionPush
- type: `object`
- propiedades:
  - `token`: string (required)
  - `projectId`: array[integer] (required)
  - `notification`: object (required)
  - `message`: string (required)
  - `type`: string (required)
  - `attachment`: array[object] (required)

## NotificarCobro
- type: `object`
- propiedades:
  - `id_cobro`: string
  - `codigo_cobro`: string
  - `fecha_transaccion`: string
  - `estado`: string
  - `transaction_result`: string

## NotificarPago
- type: `object`
- propiedades:
  - `id_deuda`: string
  - `codigo_deuda`: string
  - `fecha_pago`: string
  - `estado`: string
  - `transaction_result`: string

## Pack
- type: `object`
- propiedades:
  - `id`: integer
  - `subagrupacion`: object
  - `productosPrincipales`: array[object]
  - `productosSecundarios`: array[object]
  - `productosAdicionales`: array[object]

## Paginacion
- type: `object`
- propiedades:
  - `paginaActual`: integer - Número de la página actual
  - `totalPaginas`: integer - Total de páginas disponibles
  - `totalRegistros`: integer - Total de registros encontrados
  - `registrosPorPagina`: integer - Número de registros por página
  - `tieneAnterior`: boolean - Indica si existe página anterior
  - `tieneSiguiente`: boolean - Indica si existe página siguiente

## Pago
- type: `array`

## PokPagoResponse
- type: `object`
- propiedades:
  - `id`: string
  - `r_doc`: string
  - `r_mnt`: string
  - `r_mnv`: string
  - `r_fve`: string
  - `r_fem`: string
  - `r_des`: string
  - `cliente`: #/definitions/PokPagosCliente

## PokPagosCliente
- type: `object`
- propiedades:
  - `nombres`: string
  - `apellidos`: string
  - `identificador`: string
  - `email`: string
  - `telefono`: string

## PokPagosRequest
- type: `object`
- propiedades:
  - `codigo_interno`: string
  - `cliente`: string

## PokPagosResponse
- type: `array`

## PreguntasFrecuentes
- type: `object`
- propiedades:
  - `id_pregunta_frecuente_guia`: integer
  - `id_proyecto`: integer
  - `id_tipo_pregunta_frecuente_guia`: integer
  - `id_categoria_pregunta_frecuente_guia`: integer
  - `glosa_pregunta_frecuente_guia`: string
  - `respuesta_pregunta_frecuente_guia`: string
  - `activa`: integer

## ProductoAdicional
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `nombreEtapa`: string
  - `nombreSubagrupacion`: string
  - `proyecto`: object
  - `precio`: number/float
  - `descuentoMaximo`: #/definitions/Descuento
  - `fechaCreacion`: string/date-time
  - `fechaEdicion`: string/date-time

## ProductoCotizacion
- type: `object`
- propiedades:
  - `id`: integer (required)
  - `descuento`: #/definitions/Descuento (required)

## ProductoPrincipal
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `nombreProyecto`: string
  - `nombreEtapa`: string
  - `nombreSubagrupacion`: string
  - `nombreModelo`: string
  - `precio`: number
  - `descuentoMaximo`: #/definitions/Descuento
  - `descuentoCotizadorWeb`: #/definitions/Descuento
  - `piso`: integer
  - `orientacion`: string
  - `fechaCreacion`: string/date-time
  - `fechaEdicion`: string/date-time
  - `tipo_producto`: string
  - `programa`: string
  - `superficies`: object
  - `disponibleWeb`: boolean
  - `disponibleSistema`: boolean
  - `estado`: string (enum=['Disponible', 'Reservado', 'Promesado', 'Escriturado'])

## ProductoPrincipalBloqueo
- type: `object`
- propiedades:
  - `idCotizacion`: integer (required)

## ProductoPrincipalEstado
- type: `object`
- propiedades:
  - `id_producto`: integer
  - `activo_sistema`: integer
  - `activo_web`: integer

## ProductoPrincipalMinutos
- type: `object`
- propiedades:
  - `minutos`: integer (required)

## ProductoPrincipalUpdate
- type: `object`
- propiedades:
  - `programa`: string - Programa del producto (ej. 1D-1B)
  - `piso`: integer - Número de piso
  - `orientacion`: string - Orientación del producto
  - `tipo_producto`: string - Tipo de producto
  - `descuento_producto`: number/double - Valor del descuento
  - `descuento_producto_tipo`: string (enum=['UF', 'PORCENT']) - Tipo de descuento (UF o porcentaje)
  - `glosa_producto`: string - Descripción o glosa del producto
  - `precio`: number/double - Precio del producto
  - `id_modelo`: integer - ID del modelo asociado al producto
  - `superficies`: object

## ProductoSecundario
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `nombreEtapa`: string
  - `nombreSubagrupacion`: string
  - `proyecto`: object
  - `precio`: number/float
  - `descuentoMaximo`: #/definitions/Descuento
  - `techado`: boolean
  - `piso`: string
  - `tipo`: string
  - `disponibleWeb`: boolean
  - `disponible`: boolean
  - `fechaCreacion`: string/date-time
  - `fechaEdicion`: string/date-time
  - `estado`: string

## Promesa
- type: `object`
- propiedades:
  - `id`: integer

## PromesaServicio
- type: `object`
- propiedades:
  - `id`: integer
  - `fechaPromesa`: string/date-time
  - `totalLista`: number/float
  - `descuentoSubtotal`: #/definitions/Descuento
  - `totalVenta`: number/float
  - `estado`: string
  - `rutVendedor`: integer
  - `origenAnexo`: integer
  - `destinoAnexo`: integer
  - `fechaFirmaInmobiliaria`: string/date-time
  - `fechaFirmaComprador`: string/date-time
  - `negocio`: integer
  - `productos`: array[object]
  - `cliente`: object
  - `vendedor`: object

## Propietario
- type: `object`
- propiedades:
  - `rut`: string
  - `nombre`: string
  - `apellidoPaterno`: string
  - `apellidoMaterno`: string
  - `email`: string/email
  - `celular`: string
  - `fechaNacimiento`: string
  - `nacionalidad`: string
  - `comuna`: string
  - `provincia`: string
  - `region`: string
  - `password`: string
  - `apiKey`: string
  - `idClienteInmobiliaria`: string
  - `idProyecto`: string

## PropietarioRegistro
- type: `object`
- propiedades:
  - `rut`: string

## Provincia
- type: `object`
- propiedades:
  - `idDepartamento`: integer
  - `glosaDepartamento`: string
  - `idProvincia`: integer
  - `glosaProvincia`: string

## Proyecto
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `precioDesde`: number
  - `distrito`: string
  - `imagenes`: #/definitions/Imagenes
  - `fechaCreacion`: string/date-time
  - `fechaEdicion`: string/date-time
  - `direccion`: string
  - `activo`: boolean
  - `logo`: string
  - `codigoProyectoErp`: string
  - `descripcion`: string

## Proyectos
- type: `array`

## ProyectosPorInmobiliaria
- type: `object`
- propiedades:
  - `id`: integer
  - `glosa`: string
  - `direccion`: string
  - `inmobiliaria`: object
  - `logo`: string
  - `configuraciones`: object

## RefreshTokenCognito
- type: `object`
- propiedades:
  - `refreshToken`: string

## Region
- type: `object`
- propiedades:
  - `idRegion`: integer
  - `glosaRegion`: string
  - `numRomano`: string

## RegistroDispositivo
- type: `object`
- propiedades:
  - `rutDNI`: string
  - `idDispositivo`: string

## Reserva
- type: `object`
- propiedades:
  - `id`: integer
  - `fechaReserva`: string/date-time
  - `totalLista`: number/float
  - `descuentoSubtotal`: #/definitions/Descuento
  - `totalVenta`: number/float
  - `productos`: array[object]
  - `cliente`: object

## SubAgrupacion
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string
  - `codigo`: string
  - `codigoERP`: string
  - `fechaRM`: string
  - `fechaCreacion`: string/date-time
  - `fechaEdicion`: string/date-time

## SubAgrupaciones
- type: `array`

## TipoConsultas
- type: `array`

## TipoDocumento
- type: `object`
- propiedades:
  - `id`: integer
  - `nombre`: string

## Usuario
- type: `object`
- propiedades:
  - `dni`: integer
  - `nombre`: string
  - `apellido`: string
  - `email`: string/email
  - `telefono`: string
  - `tipoVendedor`: integer
  - `imagen`: string/hostname
  - `permisosVentas`: boolean
  - `permisosPromesa`: boolean
  - `permisosVentasPromesas`: boolean
  - `fechaCreacion`: string/date-time
  - `fechaEdicion`: string/date-time
  - `permisosAdmin`: boolean
  - `permisosSuper`: boolean

## ValidarTokenCognito
- type: `object`
- propiedades:
  - `token`: string

## VendedorByClienteResponse
- type: `array`

## Videos
- type: `array`
