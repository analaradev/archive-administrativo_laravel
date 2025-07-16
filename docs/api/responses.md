# Estructura de Respuestas y Códigos de Error

Esta documentación describe la estructura estándar de respuestas HTTP, códigos de estado y manejo de errores en el sistema administrativo Laravel.

## Códigos de Estado HTTP

### Códigos de éxito (2xx)

#### 200 OK
**Descripción**: La petición fue exitosa y el servidor devolvió los datos solicitados.

**Uso**: Páginas que se cargan correctamente (login, register, dashboard).

**Ejemplo**:
```http
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8
Set-Cookie: laravel_session=...

<!DOCTYPE html>
<html>
<!-- Contenido de la página -->
</html>
```

---

### Códigos de redirección (3xx)

#### 302 Found
**Descripción**: Redirección temporal a otra URL.

**Uso**: Después de login exitoso, logout, registro exitoso.

**Ejemplos**:

**Login exitoso**:
```http
HTTP/1.1 302 Found
Location: /dashboard
Set-Cookie: laravel_session=...
```

**Logout exitoso**:
```http
HTTP/1.1 302 Found
Location: /
Set-Cookie: laravel_session=...; expires=Thu, 01-Jan-1970 00:00:01 GMT
```

**Usuario no autenticado accediendo a ruta protegida**:
```http
HTTP/1.1 302 Found
Location: /login
```

**Usuario autenticado accediendo a login/register**:
```http
HTTP/1.1 302 Found
Location: /dashboard
```

---

### Códigos de error del cliente (4xx)

#### 404 Not Found
**Descripción**: La ruta solicitada no existe.

**Ejemplo**:
```http
HTTP/1.1 404 Not Found
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html>
<head>
    <title>404 - Página no encontrada</title>
</head>
<body>
    <h1>Página no encontrada</h1>
    <p>La página que buscas no existe.</p>
</body>
</html>
```

#### 419 Page Expired
**Descripción**: El token CSRF ha expirado o es inválido.

**Ejemplo**:
```http
HTTP/1.1 419 Page Expired
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html>
<head>
    <title>419 - Página expirada</title>
</head>
<body>
    <h1>Página expirada</h1>
    <p>Tu sesión ha expirado. Por favor, recarga la página.</p>
</body>
</html>
```

#### 422 Unprocessable Entity
**Descripción**: Error de validación en los datos enviados.

**Ejemplo**:
```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: text/html; charset=UTF-8

<!-- Página de login/register con errores de validación -->
```

---

### Códigos de error del servidor (5xx)

#### 500 Internal Server Error
**Descripción**: Error interno del servidor.

**Ejemplo**:
```http
HTTP/1.1 500 Internal Server Error
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html>
<head>
    <title>500 - Error del servidor</title>
</head>
<body>
    <h1>Error interno del servidor</h1>
    <p>Algo salió mal. Por favor, inténtalo más tarde.</p>
</body>
</html>
```

---

## Estructura de Errores de Validación

### Errores en formularios

Laravel maneja los errores de validación automáticamente. Los errores se muestran en las vistas usando la variable `$errors`.

#### Estructura de errores
```php
[
    'field_name' => [
        'Error message 1',
        'Error message 2'
    ]
]
```

#### Ejemplo en vista Blade
```html
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- O para campos específicos -->
@error('email')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror
```

---

## Errores de Autenticación

### Login fallido
**Código**: 422 Unprocessable Entity

**Errores posibles**:
```php
[
    'email' => [
        'El campo email es obligatorio.',
        'El email debe ser una dirección válida.'
    ],
    'password' => [
        'El campo contraseña es obligatorio.'
    ],
    'auth' => [
        'Las credenciales proporcionadas no coinciden con nuestros registros.'
    ]
]
```

### Registro fallido
**Código**: 422 Unprocessable Entity

**Errores posibles**:
```php
[
    'name' => [
        'El campo nombre es obligatorio.',
        'El nombre no puede tener más de 255 caracteres.'
    ],
    'email' => [
        'El campo email es obligatorio.',
        'El email debe ser una dirección válida.',
        'El email ya ha sido registrado.'
    ],
    'password' => [
        'El campo contraseña es obligatorio.',
        'La contraseña debe tener al menos 8 caracteres.',
        'La confirmación de contraseña no coincide.'
    ]
]
```

---

## Manejo de Sesiones

### Sesión expirada
**Código**: 302 Found
**Ubicación**: `/login`

```http
HTTP/1.1 302 Found
Location: /login
```

### Sesión inválida
**Código**: 302 Found
**Ubicación**: `/login`

**Nota**: Laravel invalida automáticamente sesiones comprometidas.

---

## Headers Importantes

### Seguridad
```http
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
```

### CSRF
```http
X-CSRF-TOKEN: token_value
```

### Cookies de sesión
```http
Set-Cookie: laravel_session=session_value; HttpOnly; Secure; SameSite=Lax
```

---

## Respuestas AJAX (Futuro)

### Estructura estándar para respuestas JSON

#### Respuesta exitosa
```json
{
    "success": true,
    "message": "Operación completada exitosamente",
    "data": {
        "user": {
            "id": 1,
            "name": "Usuario Ejemplo",
            "email": "usuario@example.com"
        }
    },
    "timestamp": "2025-07-15T23:00:00Z"
}
```

#### Respuesta de error
```json
{
    "success": false,
    "message": "Error en la validación",
    "errors": {
        "email": [
            "El campo email es obligatorio"
        ],
        "password": [
            "El campo contraseña es obligatorio"
        ]
    },
    "timestamp": "2025-07-15T23:00:00Z"
}
```

#### Error de autenticación
```json
{
    "success": false,
    "message": "No autenticado",
    "error_code": "UNAUTHENTICATED",
    "timestamp": "2025-07-15T23:00:00Z"
}
```

#### Error de autorización
```json
{
    "success": false,
    "message": "No autorizado",
    "error_code": "UNAUTHORIZED",
    "timestamp": "2025-07-15T23:00:00Z"
}
```

---

## Códigos de Error Personalizados

### AUTH_001: Credenciales inválidas
```json
{
    "success": false,
    "error_code": "AUTH_001",
    "message": "Las credenciales proporcionadas son incorrectas",
    "details": "Email o contraseña inválidos"
}
```

### AUTH_002: Usuario no encontrado
```json
{
    "success": false,
    "error_code": "AUTH_002",
    "message": "Usuario no encontrado",
    "details": "No existe un usuario con ese email"
}
```

### AUTH_003: Sesión expirada
```json
{
    "success": false,
    "error_code": "AUTH_003",
    "message": "Sesión expirada",
    "details": "Por favor, inicia sesión nuevamente"
}
```

### VALIDATION_001: Error de validación
```json
{
    "success": false,
    "error_code": "VALIDATION_001",
    "message": "Error en los datos enviados",
    "details": "Revisa los campos marcados en rojo",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

---

## Rate Limiting

### Límites por defecto
- **Login**: 5 intentos por minuto por IP
- **Registro**: 3 registros por minuto por IP
- **API general**: 60 peticiones por minuto

### Respuesta de rate limit excedido
**Código**: 429 Too Many Requests

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 60
Content-Type: application/json

{
    "success": false,
    "message": "Demasiadas peticiones",
    "retry_after": 60,
    "error_code": "RATE_LIMIT_EXCEEDED"
}
```

---

## Logging de Errores

### Estructura de logs
```
[2025-07-15 23:00:00] local.ERROR: Authentication failed for user
Context: {
    "email": "usuario@example.com",
    "ip": "192.168.1.1",
    "user_agent": "Mozilla/5.0...",
    "timestamp": "2025-07-15T23:00:00Z"
}
```

### Tipos de logs
- **INFO**: Login exitoso, registro exitoso
- **WARNING**: Intentos de login fallidos
- **ERROR**: Errores de servidor, excepciones
- **CRITICAL**: Errores de seguridad, ataques detectados

---

## Configuración de Errores

### Variables de entorno
```env
APP_DEBUG=false  # En producción
LOG_LEVEL=info
LOG_CHANNEL=stack
```

### Personalización de páginas de error
```
resources/views/errors/
├── 404.blade.php
├── 419.blade.php
├── 422.blade.php
├── 500.blade.php
└── layout.blade.php
```

---

## Mejores Prácticas

### Para desarrolladores frontend
1. **Siempre verificar códigos de estado** antes de procesar respuestas
2. **Manejar errores de red** y timeouts
3. **Mostrar mensajes de error** amigables al usuario
4. **Implementar retry logic** para errores temporales
5. **Validar datos** en el frontend antes de enviar

### Para desarrolladores backend
1. **Usar códigos HTTP apropiados** para cada situación
2. **Proporcionar mensajes de error** descriptivos pero seguros
3. **Loggear errores importantes** para debugging
4. **Implementar rate limiting** para prevenir abuso
5. **Validar todos los inputs** del usuario

### Para testing
1. **Probar todos los códigos de error** posibles
2. **Verificar mensajes de error** sean los esperados
3. **Testear rate limiting** y manejo de sesiones
4. **Validar headers de seguridad**
5. **Probar casos edge** y situaciones extremas

---

## Monitoreo y Alertas

### Métricas importantes
- **Tasa de errores 4xx/5xx**
- **Tiempo de respuesta promedio**
- **Intentos de login fallidos**
- **Errores de validación frecuentes**

### Alertas automáticas
- **Error rate > 5%**: Alerta inmediata
- **Response time > 2s**: Alerta de performance
- **Multiple failed logins**: Posible ataque
- **Rate limit exceeded**: Tráfico anómalo
