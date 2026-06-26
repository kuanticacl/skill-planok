# Autenticacion y base de PlanOK API

- Version Swagger: `1.16.14`
- Base URL: `https://api-gci-rest.integracionplanok.io/api`
- Consume/produce: `application/json` / `application/json`

## Esquemas de seguridad

### APIKey
- Tipo: `apiKey`
- Ubicacion: `query`
- Nombre del parametro/header: `apikey`
- Descripcion: apikey de inmobiliaria

### APIKeyPublic
- Tipo: `apiKey`
- Ubicacion: `header`
- Nombre del parametro/header: `xApiKeyPublic`
- Descripcion: X-API-KEY-PUBLIC inmobiliaria

### JWT
- Tipo: `apiKey`
- Ubicacion: `header`
- Nombre del parametro/header: `Authorization`
- Descripcion: JWT

### XAPIKey
- Tipo: `apiKey`
- Ubicacion: `header`
- Nombre del parametro/header: `X-API-KEY`
- Descripcion: apikey de inmobiliaria

### CognitoJWT
- Tipo: `apiKey`
- Ubicacion: `header`
- Nombre del parametro/header: `Token`
- Descripcion: Token Cognito

### NotificacionPush
- Tipo: `apiKey`
- Ubicacion: `query`
- Nombre del parametro/header: `codigoAplicacion`
- Descripcion: codigo aplicacion app móvil

### Planok
- Tipo: `apiKey`
- Ubicacion: `query`
- Nombre del parametro/header: `sharedSecret`
- Descripcion: planok shared secret

### SsoJWT
- Tipo: `apiKey`
- Ubicacion: `header`
- Nombre del parametro/header: `Authorization`
- Descripcion: JWT provisto por el SSO

## Reglas practicas

- No inventar credenciales. Usar placeholders como `<API_KEY>`, `<JWT>` o `<TOKEN_COGNITO>`.
- Si el endpoint declara `JWT`, usar `Authorization: Bearer <JWT>` salvo que la integracion existente use otro prefijo.
- Si el endpoint declara `APIKey`, normalmente va en query string como `?apikey=<API_KEY>`.
- Si declara `XAPIKey`, usar header `X-API-KEY: <API_KEY>`.
- Si declara `APIKeyPublic`, usar header `xApiKeyPublic: <API_KEY_PUBLIC>`.
- Si declara `CognitoJWT`, usar header `Token: <TOKEN_COGNITO>`.
- Si declara `Planok`, usar query string `sharedSecret=<SHARED_SECRET>`.