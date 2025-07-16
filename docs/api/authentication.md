# API de Autenticación

Esta documentación describe todos los endpoints relacionados con la autenticación de usuarios en el sistema administrativo Laravel.

## Base URL
```
http://localhost:8000
```

## Endpoints

### 1. Mostrar formulario de login

**GET** `/login`

Muestra la página de inicio de sesión.

#### Headers
```http
Accept: text/html
```

#### Respuesta exitosa (200)
```html
<!-- Retorna la vista de login -->
```

#### Middleware
- `guest` - Solo usuarios no autenticados

---

### 2. Procesar login

**POST** `/login`

Autentica al usuario con email y contraseña.

#### Headers
```http
Content-Type: application/x-www-form-urlencoded
Accept: text/html
X-CSRF-TOKEN: {token}
```

#### Parámetros del cuerpo
```json
{
  "email": "admin@example.com",
  "password": "admin123",
  "_token": "csrf_token_here"
}
```

#### Validaciones
- **email**: requerido, debe ser un email válido
- **password**: requerido

#### Respuesta exitosa (302)
```http
Location: /dashboard
```

#### Respuesta de error (422)
```html
<!-- Retorna a la vista de login con errores de validación -->
```

#### Errores posibles
- `email.required`: El campo email es obligatorio
- `email.email`: El email debe tener un formato válido
- `password.required`: El campo contraseña es obligatorio
- `auth.failed`: Las credenciales no coinciden

#### Middleware
- `guest` - Solo usuarios no autenticados

---

### 3. Mostrar formulario de registro

**GET** `/register`

Muestra la página de registro de nuevos usuarios.

#### Headers
```http
Accept: text/html
```

#### Respuesta exitosa (200)
```html
<!-- Retorna la vista de registro -->
```

#### Middleware
- `guest` - Solo usuarios no autenticados

---

### 4. Procesar registro

**POST** `/register`

Registra un nuevo usuario en el sistema.

#### Headers
```http
Content-Type: application/x-www-form-urlencoded
Accept: text/html
X-CSRF-TOKEN: {token}
```

#### Parámetros del cuerpo
```json
{
  "name": "Juan Pérez",
  "email": "juan@example.com",
  "password": "contraseña123",
  "password_confirmation": "contraseña123",
  "_token": "csrf_token_here"
}
```

#### Validaciones
- **name**: requerido, máximo 255 caracteres
- **email**: requerido, debe ser un email válido, único en la base de datos
- **password**: requerido, mínimo 8 caracteres, debe coincidir con la confirmación

#### Respuesta exitosa (302)
```http
Location: /dashboard
```

#### Respuesta de error (422)
```html
<!-- Retorna a la vista de registro con errores de validación -->
```

#### Errores posibles
- `name.required`: El campo nombre es obligatorio
- `name.max`: El nombre no puede tener más de 255 caracteres
- `email.required`: El campo email es obligatorio
- `email.email`: El email debe tener un formato válido
- `email.unique`: Este email ya está registrado
- `password.required`: El campo contraseña es obligatorio
- `password.min`: La contraseña debe tener al menos 8 caracteres
- `password.confirmed`: Las contraseñas no coinciden

#### Middleware
- `guest` - Solo usuarios no autenticados

---

### 5. Cerrar sesión

**POST** `/logout`

Cierra la sesión del usuario autenticado.

#### Headers
```http
Content-Type: application/x-www-form-urlencoded
Accept: text/html
X-CSRF-TOKEN: {token}
```

#### Parámetros del cuerpo
```json
{
  "_token": "csrf_token_here"
}
```

#### Respuesta exitosa (302)
```http
Location: /
```

#### Middleware
- `auth` - Solo usuarios autenticados

---

## Flujo de autenticación

### 1. Usuario no autenticado
```
GET / → Muestra welcome page con enlaces a login/register
```

### 2. Proceso de login
```
GET /login → Muestra formulario
POST /login → Valida credenciales → Redirige a /dashboard
```

### 3. Proceso de registro
```
GET /register → Muestra formulario
POST /register → Crea usuario → Autentica → Redirige a /dashboard
```

### 4. Usuario autenticado
```
GET / → Redirige automáticamente a /dashboard
GET /dashboard → Muestra dashboard del usuario
POST /logout → Cierra sesión → Redirige a /
```

## Estados de sesión

### Usuario no autenticado
- Puede acceder a: `/`, `/login`, `/register`
- No puede acceder a: `/dashboard`
- Es redirigido a `/login` al intentar acceder a rutas protegidas

### Usuario autenticado
- Puede acceder a: `/dashboard`, `/logout`
- No puede acceder a: `/login`, `/register`
- Es redirigido a `/dashboard` al intentar acceder a rutas de invitados

## Seguridad

### CSRF Protection
Todos los formularios requieren un token CSRF válido:
```html
<input type="hidden" name="_token" value="{{ csrf_token() }}">
```

### Hashing de contraseñas
Las contraseñas se almacenan usando bcrypt:
```php
Hash::make($password)
```

### Validación de sesiones
Las sesiones se validan automáticamente en cada petición mediante el middleware `auth`.

## Ejemplos de uso

### Login con cURL
```bash
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=admin@example.com&password=admin123&_token=csrf_token"
```

### Registro con cURL
```bash
curl -X POST http://localhost:8000/register \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "name=Juan Pérez&email=juan@example.com&password=contraseña123&password_confirmation=contraseña123&_token=csrf_token"
```

### Logout con cURL
```bash
curl -X POST http://localhost:8000/logout \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: laravel_session=session_cookie" \
  -d "_token=csrf_token"
```

## Códigos de respuesta

| Código | Significado | Descripción |
|--------|-------------|-------------|
| 200 | OK | Página cargada correctamente |
| 302 | Found | Redirección exitosa |
| 422 | Unprocessable Entity | Error de validación |
| 419 | Page Expired | Token CSRF inválido o expirado |
| 500 | Internal Server Error | Error del servidor |

## Notas para desarrolladores

1. **Sesiones**: Laravel maneja las sesiones automáticamente
2. **Middleware**: Los middleware `auth` y `guest` manejan la protección de rutas
3. **Validación**: Use los Request classes para validaciones complejas
4. **Redirecciones**: Los usuarios son redirigidos según su estado de autenticación
5. **CSRF**: Siempre incluir tokens CSRF en formularios
