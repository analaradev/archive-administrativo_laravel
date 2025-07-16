# Guía de Integración Frontend-Backend

Esta guía proporciona toda la información necesaria para que el equipo de frontend integre exitosamente con el backend Laravel del sistema administrativo.

## Arquitectura General

### Backend (Laravel)
- **Framework**: Laravel 12
- **Base de datos**: MariaDB/MySQL
- **Autenticación**: Sesiones tradicionales de Laravel
- **Motor de vistas**: Blade Templates
- **Build tool**: Vite para assets

### Frontend (Actual)
- **Engine**: Blade Templates con CSS/JS vanilla
- **Responsive**: Diseño adaptable
- **Build**: Vite para compilar assets
- **Estilos**: CSS modular con Tailwind-like classes

---

## Configuración del Entorno Frontend

### Instalación de dependencias
```bash
# Instalar dependencias de Node.js
npm install

# Ejecutar en modo desarrollo (watch mode)
npm run dev

# Compilar para producción
npm run build
```

### Estructura de archivos frontend
```
resources/
├── css/
│   └── app.css          # Estilos principales
├── js/
│   ├── app.js           # JavaScript principal
│   └── bootstrap.js     # Configuración de librerías
└── views/
    ├── layouts/
    │   └── app.blade.php    # Layout base
    ├── auth/
    │   ├── login.blade.php
    │   └── register.blade.php
    ├── components/
    │   └── footer.blade.php # Componente footer
    ├── dashboard.blade.php
    └── welcome.blade.php

public/build/            # Assets compilados por Vite
```

---

## Integración con Vite

### Configuración actual (vite.config.js)
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

### Uso en plantillas Blade
```html
<!-- En layouts/app.blade.php -->
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

---

## Sistema de Autenticación Frontend

### Estados de usuario

#### Usuario no autenticado
**Páginas accesibles**:
- `/` (welcome.blade.php)
- `/login` (auth/login.blade.php)
- `/register` (auth/register.blade.php)

**Comportamiento**:
- Al intentar acceder a `/dashboard` → Redirige a `/login`
- Los formularios requieren token CSRF

#### Usuario autenticado
**Páginas accesibles**:
- `/dashboard` (dashboard.blade.php)
- Puede hacer logout via POST a `/logout`

**Comportamiento**:
- Al acceder a `/` → Redirige a `/dashboard`
- Al intentar acceder a `/login` o `/register` → Redirige a `/dashboard`

### Implementación de formularios de autenticación

#### Formulario de Login
```html
<!-- resources/views/auth/login.blade.php -->
<form method="POST" action="/login" class="auth-form">
    @csrf
    
    <!-- Email -->
    <div class="form-group">
        <label for="email">Email</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            value="{{ old('email') }}"
            class="form-control @error('email') is-invalid @enderror"
            required 
            autofocus
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Password -->
    <div class="form-group">
        <label for="password">Contraseña</label>
        <input 
            type="password" 
            id="password" 
            name="password"
            class="form-control @error('password') is-invalid @enderror"
            required
        >
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Submit -->
    <button type="submit" class="btn btn-primary">
        Iniciar Sesión
    </button>
</form>
```

#### Formulario de Registro
```html
<!-- resources/views/auth/register.blade.php -->
<form method="POST" action="/register" class="auth-form">
    @csrf
    
    <!-- Name -->
    <div class="form-group">
        <label for="name">Nombre</label>
        <input 
            type="text" 
            id="name" 
            name="name" 
            value="{{ old('name') }}"
            class="form-control @error('name') is-invalid @enderror"
            required 
            autofocus
            maxlength="255"
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Email -->
    <div class="form-group">
        <label for="email">Email</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            value="{{ old('email') }}"
            class="form-control @error('email') is-invalid @enderror"
            required
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Password -->
    <div class="form-group">
        <label for="password">Contraseña</label>
        <input 
            type="password" 
            id="password" 
            name="password"
            class="form-control @error('password') is-invalid @enderror"
            required
            minlength="8"
        >
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Password Confirmation -->
    <div class="form-group">
        <label for="password_confirmation">Confirmar Contraseña</label>
        <input 
            type="password" 
            id="password_confirmation" 
            name="password_confirmation"
            class="form-control"
            required
            minlength="8"
        >
    </div>

    <!-- Submit -->
    <button type="submit" class="btn btn-primary">
        Registrarse
    </button>
</form>
```

#### Logout
```html
<!-- En cualquier parte del dashboard -->
<form method="POST" action="/logout" style="display: inline;">
    @csrf
    <button type="submit" class="btn btn-logout">
        Cerrar Sesión
    </button>
</form>
```

---

## Manejo de Errores en Frontend

### Mostrar errores de validación

#### Errores globales
```html
@if ($errors->any())
    <div class="alert alert-danger">
        <h4>Se encontraron los siguientes errores:</h4>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

#### Errores por campo
```html
<!-- Para cada campo del formulario -->
@error('field_name')
    <div class="invalid-feedback d-block">
        {{ $message }}
    </div>
@enderror

<!-- Alternativamente usando clases CSS -->
<input 
    type="text" 
    name="field_name"
    class="form-control {{ $errors->has('field_name') ? 'is-invalid' : '' }}"
>
```

### JavaScript para UX mejorada
```javascript
// resources/js/app.js

// Limpiar errores al escribir
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.form-control');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            // Remover clase de error al escribir
            this.classList.remove('is-invalid');
            
            // Ocultar mensaje de error
            const errorMsg = this.nextElementSibling;
            if (errorMsg && errorMsg.classList.contains('invalid-feedback')) {
                errorMsg.style.display = 'none';
            }
        });
    });
});

// Confirmación antes de logout
function confirmLogout() {
    return confirm('¿Estás seguro de que quieres cerrar sesión?');
}
```

---

## Datos Disponibles en las Vistas

### Usuario autenticado
```php
// Disponible en cualquier vista cuando el usuario está autenticado
Auth::user()  // Objeto User completo
Auth::id()    // ID del usuario
Auth::check() // Boolean: está autenticado?
```

#### Uso en Blade
```html
@auth
    <p>Bienvenido, {{ Auth::user()->name }}!</p>
    <p>Email: {{ Auth::user()->email }}</p>
    <p>Miembro desde: {{ Auth::user()->created_at->format('d/m/Y') }}</p>
@endauth

@guest
    <p>Por favor, <a href="/login">inicia sesión</a></p>
@endguest
```

### Datos old() para formularios
```html
<!-- Mantener valores después de error de validación -->
<input 
    type="text" 
    name="name" 
    value="{{ old('name') }}"
    placeholder="Tu nombre completo"
>

<input 
    type="email" 
    name="email" 
    value="{{ old('email') }}"
    placeholder="tu@email.com"
>
```

---

## Componentes Reutilizables

### Footer Component
```html
<!-- resources/views/components/footer.blade.php -->
<footer class="site-footer">
    <div class="footer-content">
        <p>&copy; {{ date('Y') }} Sistema Administrativo Laravel</p>
        <a href="https://github.com/ederjgb94" 
           target="_blank" 
           rel="noopener noreferrer"
           class="github-link">
            <svg class="github-icon" viewBox="0 0 24 24">
                <!-- SVG del icono de GitHub -->
            </svg>
            GitHub
        </a>
    </div>
</footer>
```

#### Uso del componente
```html
<!-- En cualquier vista -->
<x-footer />

<!-- O usando include -->
@include('components.footer')
```

### Layout Base
```html
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Sistema Administrativo')</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="@yield('body-class')">
    <div id="app">
        @yield('content')
    </div>
    
    @stack('scripts')
</body>
</html>
```

---

## Responsive Design

### Breakpoints recomendados
```css
/* Mobile first approach */
/* Móvil: por defecto */

/* Tablet */
@media (min-width: 768px) {
    /* Estilos para tablet */
}

/* Desktop */
@media (min-width: 1024px) {
    /* Estilos para desktop */
}

/* Desktop grande */
@media (min-width: 1280px) {
    /* Estilos para pantallas grandes */
}
```

### Clases utility disponibles
```css
/* Display */
.d-none { display: none; }
.d-block { display: block; }
.d-flex { display: flex; }

/* Flex */
.justify-center { justify-content: center; }
.items-center { align-items: center; }
.flex-col { flex-direction: column; }

/* Spacing */
.p-4 { padding: 1rem; }
.m-4 { margin: 1rem; }
.mb-4 { margin-bottom: 1rem; }

/* Colors */
.text-primary { color: #3b82f6; }
.bg-white { background-color: white; }
.text-red-500 { color: #ef4444; }
```

---

## Integración con APIs Futuras

### Configuración para AJAX
```javascript
// Setup CSRF token para peticiones AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Configurar token CSRF para todas las peticiones AJAX
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Para fetch()
    window.csrfToken = token;
    
    // Para axios si se usa en el futuro
    if (window.axios) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
});

// Función helper para peticiones
async function apiRequest(url, options = {}) {
    const defaultOptions = {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    };
    
    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    };
    
    try {
        const response = await fetch(url, mergedOptions);
        return await response.json();
    } catch (error) {
        console.error('API Request failed:', error);
        throw error;
    }
}
```

### Ejemplo de uso futuro
```javascript
// Login via AJAX (cuando se implemente)
async function loginViaAjax(email, password) {
    try {
        const response = await apiRequest('/api/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
        
        if (response.success) {
            window.location.href = '/dashboard';
        } else {
            // Mostrar errores
            displayErrors(response.errors);
        }
    } catch (error) {
        // Manejar error de red
        showErrorMessage('Error de conexión');
    }
}
```

---

## Testing Frontend

### Testing de formularios
```javascript
// Ejemplo con Jest/Testing Library (futuro)
test('login form submits with valid data', () => {
    // Simular llenado de formulario
    const emailInput = screen.getByLabelText(/email/i);
    const passwordInput = screen.getByLabelText(/contraseña/i);
    const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });
    
    fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
    fireEvent.change(passwordInput, { target: { value: 'password123' } });
    fireEvent.click(submitButton);
    
    // Verificar que se envió el formulario
    expect(submitButton).toBeDisabled();
});
```

### Testing de validación
```javascript
test('shows validation errors', () => {
    // Enviar formulario vacío
    const submitButton = screen.getByRole('button', { name: /iniciar sesión/i });
    fireEvent.click(submitButton);
    
    // Verificar errores
    expect(screen.getByText(/el campo email es obligatorio/i)).toBeInTheDocument();
    expect(screen.getByText(/el campo contraseña es obligatorio/i)).toBeInTheDocument();
});
```

---

## Mejores Prácticas

### Seguridad
1. **Siempre usar @csrf** en formularios
2. **Escapar contenido del usuario** con `{{ }}` en lugar de `{!! !!}`
3. **Validar datos** en el frontend y backend
4. **Usar HTTPS** en producción

### Performance
1. **Lazy loading** de imágenes y contenido
2. **Minificar CSS/JS** en producción
3. **Optimizar imágenes** antes de usar
4. **Usar CDN** para assets estáticos

### UX/UI
1. **Feedback visual** para acciones del usuario
2. **Loading states** durante peticiones
3. **Mensajes de error** claros y útiles
4. **Navegación intuitiva**

### Mantenibilidad
1. **Componentes reutilizables** para UI común
2. **CSS modular** y bien organizado
3. **JavaScript** organizado en módulos
4. **Documentar** componentes complejos

---

## Roadmap de Integración

### Fase 1: Mejorar UI actual
- [ ] Mejorar estilos de formularios
- [ ] Añadir animaciones y transiciones
- [ ] Optimizar responsive design
- [ ] Mejorar accesibilidad

### Fase 2: Interactividad
- [ ] Validación en tiempo real
- [ ] AJAX para formularios
- [ ] Loading states
- [ ] Notificaciones toast

### Fase 3: Componentes avanzados
- [ ] Dashboard interactivo
- [ ] Widgets personalizables
- [ ] Sistema de notificaciones
- [ ] Modo oscuro/claro

### Fase 4: PWA
- [ ] Service Workers
- [ ] Offline capabilities
- [ ] Push notifications
- [ ] App manifest

---

## Contacto y Soporte

Para dudas sobre la integración frontend:
1. **Revisar esta documentación** primero
2. **Consultar las APIs** documentadas
3. **Probar en el entorno de desarrollo**
4. **Crear issue** en GitHub si es necesario

**Nota**: Esta documentación se actualizará conforme evolucione la API y se añadan nuevas funcionalidades.
