# Flujo de Autenticación para Frontend

Esta documentación describe en detalle cómo funciona el sistema de autenticación desde la perspectiva del frontend, incluyendo todos los estados, transiciones y casos especiales.

## Diagrama de Estados

```
                    ┌─────────────────┐
                    │   USUARIO NO    │
                    │  AUTENTICADO    │
                    └─────────────────┘
                             │
                    ┌────────▼────────┐
                    │   GET /login    │
                    │  GET /register  │
                    │     GET /       │
                    └─────────────────┘
                             │
                ┌────────────▼────────────┐
                │   POST /login   │   POST /register   │
                │   (Credenciales) │    (Datos de usuario) │
                └─────────────────────────┘
                             │
                      ┌──────▼──────┐
                      │ ¿Válido?    │
                      └─────────────┘
                       │           │
                 ┌─────▼─────┐ ┌───▼───┐
                 │    SÍ     │ │   NO  │
                 └───────────┘ └───────┘
                       │           │
                 ┌─────▼─────┐ ┌───▼───┐
                 │Dashboard  │ │Errors │
                 │Autenticado│ │ 422   │
                 └───────────┘ └───────┘
                       │
                 ┌─────▼─────┐
                 │POST/logout│
                 └───────────┘
                       │
                 ┌─────▼─────┐
                 │  Welcome  │
                 │No auth    │
                 └───────────┘
```

---

## Estados del Usuario

### 1. Estado: No Autenticado

#### Características
- **Sesión**: No existe o está expirada
- **Cookie**: `laravel_session` inexistente o inválida
- **Auth::check()**: `false`
- **Auth::user()**: `null`

#### Páginas accesibles
```
✅ GET /           → welcome.blade.php
✅ GET /login      → auth/login.blade.php  
✅ GET /register   → auth/register.blade.php
❌ GET /dashboard  → Redirect to /login
```

#### Comportamiento automático
```php
// Cualquier intento de acceder a rutas protegidas
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Redirige automáticamente a /login con el intended URL
```

### 2. Estado: Autenticado

#### Características
- **Sesión**: Válida y activa
- **Cookie**: `laravel_session` con token válido
- **Auth::check()**: `true`
- **Auth::user()**: Objeto User completo

#### Páginas accesibles
```
✅ GET /dashboard  → dashboard.blade.php
✅ POST /logout    → Cierra sesión
❌ GET /login      → Redirect to /dashboard
❌ GET /register   → Redirect to /dashboard
❌ GET /          → Redirect to /dashboard (si está autenticado)
```

#### Datos disponibles
```php
// En cualquier vista del usuario autenticado
$user = Auth::user();
// {
//     "id": 1,
//     "name": "Usuario Ejemplo", 
//     "email": "usuario@example.com",
//     "created_at": "2025-07-15T23:00:00Z",
//     "updated_at": "2025-07-15T23:00:00Z"
// }
```

---

## Flujos de Autenticación

### Flujo 1: Login Exitoso

#### Paso a paso
```
1. Usuario no autenticado accede a GET /login
   ↓
2. Se muestra formulario de login (auth/login.blade.php)
   ↓
3. Usuario llena email y password
   ↓
4. Submit POST /login con datos + CSRF token
   ↓
5. Laravel valida datos:
   - Email requerido y formato válido
   - Password requerido
   - Credenciales correctas en BD
   ↓
6. Si válido:
   - Auth::login($user) 
   - Crear sesión
   - Redirect 302 a /dashboard
   ↓
7. Usuario llega a dashboard autenticado
```

#### Código de implementación
```html
<!-- auth/login.blade.php -->
<form method="POST" action="/login">
    @csrf
    
    <div class="form-group">
        <input type="email" name="email" value="{{ old('email') }}" required>
        @error('email')<span class="error">{{ $message }}</span>@enderror
    </div>
    
    <div class="form-group">
        <input type="password" name="password" required>
        @error('password')<span class="error">{{ $message }}</span>@enderror
    </div>
    
    <button type="submit">Iniciar Sesión</button>
</form>
```

#### Respuesta exitosa
```http
HTTP/1.1 302 Found
Location: /dashboard
Set-Cookie: laravel_session=eyJ...; HttpOnly; Secure; SameSite=Lax
```

### Flujo 2: Login Fallido

#### Errores de validación
```
1. Usuario envía POST /login con datos inválidos
   ↓
2. Laravel valida y encuentra errores:
   - Email vacío o formato inválido
   - Password vacío
   - Credenciales incorrectas
   ↓
3. Respuesta 422 Unprocessable Entity
   ↓
4. Redirect back to /login con errores y old input
   ↓
5. Formulario se muestra con:
   - Mensajes de error
   - Datos previamente ingresados (old)
```

#### Manejo de errores en Blade
```html
@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<!-- Por campo específico -->
<input type="email" name="email" value="{{ old('email') }}" 
       class="@error('email') is-invalid @enderror">
@error('email')
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
```

### Flujo 3: Registro Exitoso

#### Paso a paso
```
1. Usuario accede a GET /register
   ↓
2. Se muestra formulario de registro
   ↓
3. Usuario llena: name, email, password, password_confirmation
   ↓
4. Submit POST /register con datos + CSRF
   ↓
5. Laravel valida:
   - Name requerido, max 255 chars
   - Email requerido, válido, único
   - Password min 8 chars, confirmed
   ↓
6. Si válido:
   - User::create() con password hasheado
   - Auth::login($user) automático
   - Redirect 302 a /dashboard
   ↓
7. Usuario autenticado en dashboard
```

#### Validaciones específicas
```php
// En AuthController
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|string|email|max:255|unique:users',
    'password' => 'required|string|min:8|confirmed',
]);
```

### Flujo 4: Logout

#### Paso a paso
```
1. Usuario autenticado en dashboard
   ↓
2. Click en botón "Cerrar Sesión"
   ↓
3. Submit POST /logout con CSRF token
   ↓
4. Laravel ejecuta:
   - Auth::logout()
   - Invalidar sesión
   - Regenerar CSRF token
   ↓
5. Redirect 302 a /
   ↓
6. Usuario no autenticado en welcome page
```

#### Implementación del logout
```html
<!-- En dashboard o cualquier página autenticada -->
<form method="POST" action="/logout" class="logout-form">
    @csrf
    <button type="submit" onclick="return confirm('¿Seguro que quieres cerrar sesión?')">
        Cerrar Sesión
    </button>
</form>
```

---

## Redirecciones Automáticas

### Middleware de autenticación (`auth`)

#### Comportamiento
```php
// Si usuario NO está autenticado y trata de acceder a ruta protegida
if (!Auth::check()) {
    return redirect('/login')->with('intended', $request->url());
}
```

#### Ejemplo práctico
```
1. Usuario no autenticado va a: GET /dashboard
   ↓
2. Middleware detecta no autenticado
   ↓
3. Redirect a: GET /login?intended=/dashboard
   ↓
4. Después de login exitoso: Redirect a /dashboard (intended URL)
```

### Middleware de invitados (`guest`)

#### Comportamiento
```php
// Si usuario YA está autenticado y trata de acceder a login/register
if (Auth::check()) {
    return redirect('/dashboard');
}
```

#### Ejemplo práctico
```
1. Usuario autenticado va a: GET /login
   ↓
2. Middleware detecta ya autenticado
   ↓
3. Redirect automático a: GET /dashboard
```

---

## Estados de Sesión

### Sesión válida

#### Características
- **Tiempo de vida**: 120 minutos por defecto
- **Renovación**: Se extiende en cada petición
- **Storage**: Archivos en `storage/framework/sessions/`
- **Cookie**: `laravel_session` con token cifrado

#### Verificación en Blade
```html
@auth
    <!-- Usuario autenticado -->
    <p>Bienvenido, {{ Auth::user()->name }}!</p>
@endauth

@guest
    <!-- Usuario no autenticado -->
    <a href="/login">Iniciar Sesión</a>
@endguest
```

### Sesión expirada

#### Qué pasa cuando expira
```
1. Usuario intenta acceder a ruta protegida
   ↓
2. Laravel verifica sesión
   ↓
3. Sesión inválida/expirada
   ↓
4. Auth::check() = false
   ↓
5. Redirect automático a /login
   ↓
6. Mensaje: "Tu sesión ha expirado"
```

#### Implementación del mensaje
```html
@if (session('message'))
    <div class="alert alert-info">
        {{ session('message') }}
    </div>
@endif
```

---

## Manejo de Errores Específicos

### Error 419: Page Expired (CSRF)

#### Cuándo ocurre
- Token CSRF expirado
- Token CSRF faltante
- Token CSRF inválido

#### Respuesta
```http
HTTP/1.1 419 Page Expired
```

#### Manejo en frontend
```javascript
// Detectar error 419 y recargar página
if (response.status === 419) {
    alert('Tu sesión ha expirado. La página se recargará.');
    window.location.reload();
}
```

### Error 422: Validation Failed

#### Estructura de errores
```php
// Laravel retorna errores en este formato
$errors = [
    'email' => ['El campo email es obligatorio.'],
    'password' => ['El campo contraseña es obligatorio.']
];
```

#### Mostrar en frontend
```html
<!-- Errores generales -->
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Errores por campo -->
@error('email')
    <div class="text-red-500 text-sm">{{ $message }}</div>
@enderror
```

---

## Casos Especiales

### Usuario accede a raíz (/)

#### Lógica de redirección
```php
// En routes/web.php
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');  // Usuario autenticado
    }
    return view('welcome');             // Usuario no autenticado
});
```

### Múltiples pestañas/ventanas

#### Comportamiento esperado
- **Sesión compartida**: Login en una pestaña = autenticado en todas
- **Logout compartido**: Logout en una pestaña = deslogueado en todas
- **Expiración**: Si expira en una, expira en todas

#### Implementación para sincronización
```javascript
// Detectar cambios de autenticación entre pestañas
window.addEventListener('storage', function(e) {
    if (e.key === 'auth_status') {
        if (e.newValue === 'logged_out') {
            window.location.href = '/login';
        }
    }
});

// Al hacer logout, notificar otras pestañas
function logout() {
    localStorage.setItem('auth_status', 'logged_out');
    // Proceder con logout normal
}
```

### Remember Me (Funcionalidad futura)

#### Cómo implementar
```html
<!-- En login form -->
<div class="form-check">
    <input type="checkbox" name="remember" id="remember">
    <label for="remember">Recordarme</label>
</div>
```

```php
// En AuthController
if ($request->remember) {
    Auth::login($user, true); // remember = true
}
```

---

## Testing del Flujo de Autenticación

### Test de login exitoso
```php
public function test_user_can_login_with_valid_credentials()
{
    $user = User::factory()->create(['password' => Hash::make('password')]);
    
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
}
```

### Test de login fallido
```php
public function test_user_cannot_login_with_invalid_credentials()
{
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);
    
    $response->assertSessionHasErrors();
    $this->assertGuest();
}
```

### Test de redirecciones
```php
public function test_authenticated_user_redirected_from_login()
{
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $response = $this->get('/login');
    $response->assertRedirect('/dashboard');
}
```

---

## Optimizaciones de UX

### Loading states
```javascript
// Mostrar spinner durante login
document.querySelector('form').addEventListener('submit', function() {
    const button = this.querySelector('button[type="submit"]');
    button.disabled = true;
    button.innerHTML = 'Iniciando sesión...';
});
```

### Validación en tiempo real
```javascript
// Validar email en tiempo real
document.getElementById('email').addEventListener('blur', function() {
    const email = this.value;
    if (!isValidEmail(email)) {
        showFieldError(this, 'Email inválido');
    } else {
        clearFieldError(this);
    }
});
```

### Preservar estado del formulario
```html
<!-- Mantener valores después de error -->
<input type="email" name="email" value="{{ old('email') }}">
<input type="text" name="name" value="{{ old('name') }}">
```

---

## Debugging del Flujo

### Variables útiles para debugging
```php
// En cualquier controlador o vista
dd(Auth::check());          // true/false
dd(Auth::user());           // User object o null  
dd(session()->all());       // Toda la información de sesión
dd(request()->session()->getId()); // ID de sesión actual
```

### Logs de autenticación
```php
// En AuthController, agregar logs
Log::info('Login attempt', ['email' => $request->email]);
Log::info('Login successful', ['user_id' => Auth::id()]);
Log::info('Logout', ['user_id' => Auth::id()]);
```

### Debugging en Blade
```html
@if(app()->environment('local'))
    <div class="debug-info">
        <p>Auth check: {{ Auth::check() ? 'true' : 'false' }}</p>
        <p>User ID: {{ Auth::id() ?? 'null' }}</p>
        <p>Session ID: {{ session()->getId() }}</p>
    </div>
@endif
```

---

## Mejores Prácticas

### Seguridad
1. **Siempre usar CSRF tokens** en formularios
2. **Validar datos** en servidor, no solo cliente
3. **Hashear contraseñas** con bcrypt
4. **Usar HTTPS** en producción
5. **Configurar timeouts** apropiados de sesión

### UX
1. **Preservar datos** en errores de validación
2. **Mensajes claros** de error
3. **Loading states** durante procesamiento
4. **Confirmación** antes de logout
5. **Breadcrumbs** para navegación

### Performance
1. **Lazy load** contenido del dashboard
2. **Cache** consultas frecuentes
3. **Optimizar** consultas de autenticación
4. **Minimizar** redirecciones innecesarias

---

## Checklist para Implementación

### Frontend Developer Checklist
- [ ] Formularios tienen tokens CSRF
- [ ] Errores se muestran correctamente  
- [ ] Old input se preserva en errores
- [ ] Loading states implementados
- [ ] Responsive design verificado
- [ ] Navegación coherente entre estados
- [ ] Logout funciona correctamente
- [ ] Redirecciones automáticas funcionan
- [ ] Mensajes de usuario son claros
- [ ] Testing de flujos principales

Esta documentación cubre todos los aspectos del flujo de autenticación desde la perspectiva del frontend. ¡Utilízala como referencia completa para implementar y mantener la autenticación de usuarios!
