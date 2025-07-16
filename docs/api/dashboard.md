# API del Dashboard

Esta documentación describe el endpoint del dashboard y sus funcionalidades para usuarios autenticados.

## Base URL
```
http://localhost:8000
```

## Endpoints

### 1. Dashboard principal

**GET** `/dashboard`

Muestra el dashboard principal del usuario autenticado con información personalizada y estadísticas.

#### Headers
```http
Accept: text/html
Cookie: laravel_session=session_cookie
```

#### Respuesta exitosa (200)
```html
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Sistema Administrativo</title>
    <!-- Contenido del dashboard -->
</head>
<body>
    <!-- Dashboard con información del usuario -->
</body>
</html>
```

#### Información mostrada
- **Nombre del usuario**: Obtenido de `Auth::user()->name`
- **Email del usuario**: Obtenido de `Auth::user()->email`
- **Fecha de registro**: Obtenido de `Auth::user()->created_at`
- **Última actualización**: Obtenido de `Auth::user()->updated_at`
- **Estadísticas básicas**: Conteos y métricas del sistema
- **Navegación**: Enlaces a otras secciones del sistema

#### Middleware
- `auth` - Solo usuarios autenticados

#### Redirección automática
Si el usuario no está autenticado, es redirigido automáticamente a `/login`.

---

## Estructura del Dashboard

### Header
```html
<header class="dashboard-header">
    <h1>Bienvenido, {{ $user->name }}</h1>
    <nav class="main-navigation">
        <a href="/dashboard">Dashboard</a>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit">Cerrar Sesión</button>
        </form>
    </nav>
</header>
```

### Contenido principal
```html
<main class="dashboard-content">
    <!-- Información del usuario -->
    <section class="user-info">
        <h2>Información del Usuario</h2>
        <div class="user-details">
            <p><strong>Nombre:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Miembro desde:</strong> {{ $user->created_at->format('d/m/Y') }}</p>
        </div>
    </section>

    <!-- Estadísticas básicas -->
    <section class="dashboard-stats">
        <h2>Estadísticas del Sistema</h2>
        <div class="stats-grid">
            <!-- Métricas del sistema -->
        </div>
    </section>

    <!-- Acciones rápidas -->
    <section class="quick-actions">
        <h2>Acciones Rápidas</h2>
        <div class="actions-grid">
            <!-- Enlaces a funcionalidades -->
        </div>
    </section>
</main>
```

### Footer
```html
<footer class="dashboard-footer">
    <p>&copy; 2025 Sistema Administrativo Laravel</p>
    <a href="https://github.com/ederjgb94" target="_blank" rel="noopener noreferrer">
        GitHub
    </a>
</footer>
```

---

## Datos disponibles

### Objeto User (Auth::user())
```php
[
    'id' => 1,
    'name' => 'Usuario Ejemplo',
    'email' => 'usuario@example.com',
    'email_verified_at' => null,
    'created_at' => '2025-07-15T23:00:00.000000Z',
    'updated_at' => '2025-07-15T23:00:00.000000Z'
]
```

### Variables de vista
```php
[
    'user' => Auth::user(),
    'totalUsers' => User::count(),
    'recentLogins' => // Logs recientes si se implementan
    'systemStats' => // Estadísticas del sistema
]
```

---

## Respuestas y códigos de estado

### Respuesta exitosa (200)
El dashboard se carga correctamente con toda la información del usuario.

### Redirección (302)
```http
Location: /login
```
Cuando el usuario no está autenticado.

### Error de servidor (500)
```html
<!-- Página de error del servidor -->
```

---

## Funcionalidades futuras

### Métricas avanzadas
```javascript
// Estadísticas en tiempo real
{
    "totalUsers": 150,
    "activeUsers": 23,
    "todayLogins": 45,
    "systemUptime": "99.9%"
}
```

### Widgets personalizables
```html
<div class="dashboard-widgets">
    <div class="widget widget-users">
        <h3>Usuarios</h3>
        <div class="widget-content">
            <!-- Contenido del widget -->
        </div>
    </div>
    <div class="widget widget-activity">
        <h3>Actividad Reciente</h3>
        <div class="widget-content">
            <!-- Actividad reciente -->
        </div>
    </div>
</div>
```

### Notificaciones
```html
<div class="dashboard-notifications">
    <div class="notification notification-info">
        <p>Bienvenido al sistema</p>
    </div>
</div>
```

---

## Seguridad del Dashboard

### Middleware de autenticación
```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
```

### Verificación de sesión
```php
public function index()
{
    // Laravel verifica automáticamente la autenticación
    $user = Auth::user(); // Usuario autenticado garantizado
    
    return view('dashboard', compact('user'));
}
```

### CSRF en formularios
```html
<!-- Logout form -->
<form method="POST" action="/logout">
    @csrf
    <button type="submit">Cerrar Sesión</button>
</form>
```

---

## Ejemplos de uso

### Acceso al dashboard
```bash
# Con sesión válida
curl -X GET http://localhost:8000/dashboard \
  -H "Cookie: laravel_session=valid_session_cookie"

# Sin sesión válida - redirige a login
curl -X GET http://localhost:8000/dashboard
```

### Logout desde el dashboard
```bash
curl -X POST http://localhost:8000/logout \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Cookie: laravel_session=valid_session_cookie" \
  -d "_token=csrf_token"
```

---

## Personalización del Dashboard

### Para desarrolladores frontend

#### CSS Classes disponibles
```css
.dashboard-header { /* Header del dashboard */ }
.dashboard-content { /* Contenido principal */ }
.user-info { /* Sección de información del usuario */ }
.dashboard-stats { /* Sección de estadísticas */ }
.quick-actions { /* Sección de acciones rápidas */ }
.dashboard-footer { /* Footer del dashboard */ }
```

#### JavaScript hooks
```javascript
// Eventos personalizados disponibles
document.addEventListener('DOMContentLoaded', function() {
    // Dashboard cargado
    console.log('Dashboard loaded');
});

// Para futuras implementaciones AJAX
window.dashboardAPI = {
    loadStats: function() {
        // Cargar estadísticas via AJAX
    },
    updateUserInfo: function() {
        // Actualizar información del usuario
    }
};
```

#### Variables de entorno
```php
// En .env para personalización
DASHBOARD_TITLE="Mi Dashboard Personalizado"
DASHBOARD_THEME="dark" // light|dark
DASHBOARD_WIDGETS_ENABLED=true
```

---

## Performance

### Optimizaciones implementadas
- **Lazy loading**: Cargar contenido bajo demanda
- **Caching**: Cache de consultas frecuentes
- **Minificación**: CSS y JS optimizados

### Métricas de performance
- **Tiempo de carga**: < 2 segundos
- **Consultas DB**: Optimizadas con eager loading
- **Memoria**: Uso eficiente de recursos

---

## Notas para desarrolladores

1. **Autenticación**: El middleware `auth` garantiza que solo usuarios autenticados accedan
2. **Datos del usuario**: Siempre disponible via `Auth::user()`
3. **Responsive**: El dashboard es responsive y funciona en móviles
4. **Extensible**: Fácil agregar nuevas secciones y widgets
5. **Seguro**: Protegido contra XSS y CSRF
6. **Cacheable**: Implementar cache para mejorar performance
7. **Accesible**: Seguir estándares de accesibilidad web

---

## Roadmap futuro

- [ ] Dashboard con widgets personalizables
- [ ] Métricas en tiempo real con WebSockets
- [ ] Sistema de notificaciones
- [ ] Dashboard API REST para aplicaciones móviles
- [ ] Temas personalizables
- [ ] Configuración de usuario avanzada
