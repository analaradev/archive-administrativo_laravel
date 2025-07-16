# Ejemplos de API para Frontend

Esta documentación proporciona ejemplos prácticos y código listo para usar que el equipo de frontend puede implementar directamente en sus aplicaciones.

## Configuración Inicial

### 1. Setup básico de JavaScript

```javascript
// resources/js/auth.js
class AuthAPI {
    constructor() {
        this.baseURL = window.location.origin;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    // Helper para hacer peticiones con CSRF
    async makeRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'text/html,application/json'
            },
            credentials: 'same-origin'
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        return fetch(url, mergedOptions);
    }

    // Convertir objeto a FormData para envío
    objectToFormData(obj) {
        const formData = new FormData();
        for (const key in obj) {
            formData.append(key, obj[key]);
        }
        return formData;
    }
}

// Instancia global
window.authAPI = new AuthAPI();
```

### 2. Setup de CSRF Token en layout

```html
<!-- resources/views/layouts/app.blade.php -->
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- otros meta tags -->
</head>
```

---

## Ejemplos de Login

### 1. Login básico con manejo de errores

```javascript
// Función para login
async function loginUser(email, password) {
    const loginForm = document.querySelector('#login-form');
    const submitButton = loginForm.querySelector('button[type="submit"]');
    const errorContainer = document.querySelector('#error-messages');
    
    try {
        // Mostrar loading state
        submitButton.disabled = true;
        submitButton.textContent = 'Iniciando sesión...';
        
        // Limpiar errores previos
        clearErrors();
        
        // Hacer petición
        const response = await window.authAPI.makeRequest('/login', {
            method: 'POST',
            body: window.authAPI.objectToFormData({
                email: email,
                password: password,
                _token: window.authAPI.csrfToken
            })
        });
        
        if (response.redirected) {
            // Login exitoso - redirigir
            window.location.href = response.url;
        } else {
            // Errores de validación
            const text = await response.text();
            parseAndShowErrors(text);
        }
        
    } catch (error) {
        showError('Error de conexión. Por favor, inténtalo de nuevo.');
        console.error('Login error:', error);
    } finally {
        // Restaurar botón
        submitButton.disabled = false;
        submitButton.textContent = 'Iniciar Sesión';
    }
}

// Event listener para el formulario
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('#login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[name="email"]').value;
            const password = this.querySelector('input[name="password"]').value;
            
            loginUser(email, password);
        });
    }
});
```

### 2. Validación en tiempo real

```javascript
// Validación de email en tiempo real
function setupEmailValidation() {
    const emailInput = document.querySelector('input[name="email"]');
    if (!emailInput) return;
    
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        
        if (!email) {
            showFieldError(this, 'El email es obligatorio');
            return;
        }
        
        if (!isValidEmail(email)) {
            showFieldError(this, 'Por favor ingresa un email válido');
            return;
        }
        
        clearFieldError(this);
    });
    
    // Limpiar error al escribir
    emailInput.addEventListener('input', function() {
        clearFieldError(this);
    });
}

// Función para validar email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Funciones de UI para errores
function showFieldError(input, message) {
    input.classList.add('is-invalid');
    
    let errorDiv = input.nextElementSibling;
    if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        input.parentNode.insertBefore(errorDiv, input.nextSibling);
    }
    
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
}

function clearFieldError(input) {
    input.classList.remove('is-invalid');
    
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
        errorDiv.style.display = 'none';
    }
}
```

---

## Ejemplos de Registro

### 1. Registro con validación completa

```javascript
// Función para registro
async function registerUser(userData) {
    const registerForm = document.querySelector('#register-form');
    const submitButton = registerForm.querySelector('button[type="submit"]');
    
    try {
        submitButton.disabled = true;
        submitButton.textContent = 'Creando cuenta...';
        
        clearErrors();
        
        // Validar en frontend antes de enviar
        if (!validateRegistrationData(userData)) {
            return;
        }
        
        const response = await window.authAPI.makeRequest('/register', {
            method: 'POST',
            body: window.authAPI.objectToFormData({
                ...userData,
                _token: window.authAPI.csrfToken
            })
        });
        
        if (response.redirected) {
            // Registro exitoso
            window.location.href = response.url;
        } else {
            // Errores de validación del servidor
            const text = await response.text();
            parseAndShowErrors(text);
        }
        
    } catch (error) {
        showError('Error al crear la cuenta. Por favor, inténtalo de nuevo.');
        console.error('Registration error:', error);
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Crear Cuenta';
    }
}

// Validación frontend para registro
function validateRegistrationData(data) {
    let isValid = true;
    
    // Validar nombre
    if (!data.name || data.name.trim().length < 2) {
        showFieldError(
            document.querySelector('input[name="name"]'),
            'El nombre debe tener al menos 2 caracteres'
        );
        isValid = false;
    }
    
    // Validar email
    if (!data.email || !isValidEmail(data.email)) {
        showFieldError(
            document.querySelector('input[name="email"]'),
            'Por favor ingresa un email válido'
        );
        isValid = false;
    }
    
    // Validar contraseña
    if (!data.password || data.password.length < 8) {
        showFieldError(
            document.querySelector('input[name="password"]'),
            'La contraseña debe tener al menos 8 caracteres'
        );
        isValid = false;
    }
    
    // Validar confirmación de contraseña
    if (data.password !== data.password_confirmation) {
        showFieldError(
            document.querySelector('input[name="password_confirmation"]'),
            'Las contraseñas no coinciden'
        );
        isValid = false;
    }
    
    return isValid;
}

// Event listener para registro
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.querySelector('#register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const userData = {
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                password_confirmation: formData.get('password_confirmation')
            };
            
            registerUser(userData);
        });
    }
});
```

### 2. Verificación de email disponible (ejemplo para futuro)

```javascript
// Función para verificar si email está disponible
async function checkEmailAvailability(email) {
    if (!isValidEmail(email)) return;
    
    try {
        // Este endpoint no existe aún, es para referencia futura
        const response = await fetch(`/api/check-email?email=${encodeURIComponent(email)}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.authAPI.csrfToken
            }
        });
        
        const data = await response.json();
        
        const emailInput = document.querySelector('input[name="email"]');
        if (data.available) {
            clearFieldError(emailInput);
            showFieldSuccess(emailInput, 'Email disponible');
        } else {
            showFieldError(emailInput, 'Este email ya está registrado');
        }
        
    } catch (error) {
        console.error('Error checking email:', error);
    }
}

// Debounce para evitar múltiples requests
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Setup de verificación de email con debounce
const debouncedEmailCheck = debounce(checkEmailAvailability, 500);

document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.querySelector('#register-form input[name="email"]');
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            const email = this.value.trim();
            if (email && isValidEmail(email)) {
                debouncedEmailCheck(email);
            }
        });
    }
});
```

---

## Ejemplos de Dashboard

### 1. Carga dinámica de datos del usuario

```javascript
// Funciones para el dashboard
class Dashboard {
    constructor() {
        this.userDataContainer = document.querySelector('#user-data');
        this.statsContainer = document.querySelector('#stats-container');
    }
    
    // Actualizar información del usuario
    updateUserInfo(userData) {
        if (!this.userDataContainer) return;
        
        this.userDataContainer.innerHTML = `
            <div class="user-card">
                <h3>Información Personal</h3>
                <div class="user-details">
                    <p><strong>Nombre:</strong> ${this.escapeHtml(userData.name)}</p>
                    <p><strong>Email:</strong> ${this.escapeHtml(userData.email)}</p>
                    <p><strong>Miembro desde:</strong> ${this.formatDate(userData.created_at)}</p>
                    <p><strong>Última actualización:</strong> ${this.formatDate(userData.updated_at)}</p>
                </div>
            </div>
        `;
    }
    
    // Función para escapar HTML (seguridad)
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Formatear fechas
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    // Mostrar estadísticas (para implementación futura)
    updateStats(stats) {
        if (!this.statsContainer) return;
        
        this.statsContainer.innerHTML = `
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Usuarios</h4>
                    <p class="stat-number">${stats.totalUsers || 0}</p>
                </div>
                <div class="stat-card">
                    <h4>Usuarios Activos</h4>
                    <p class="stat-number">${stats.activeUsers || 0}</p>
                </div>
                <div class="stat-card">
                    <h4>Logins Hoy</h4>
                    <p class="stat-number">${stats.todayLogins || 0}</p>
                </div>
            </div>
        `;
    }
}

// Inicializar dashboard
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.dashboard-content')) {
        const dashboard = new Dashboard();
        
        // Los datos del usuario ya están disponibles en la vista Blade
        // En una implementación futura con AJAX, se cargarían dinámicamente
        
        // Ejemplo de datos que vendrían del servidor
        const userData = {
            name: document.querySelector('[data-user-name]')?.textContent || '',
            email: document.querySelector('[data-user-email]')?.textContent || '',
            created_at: document.querySelector('[data-user-created]')?.textContent || '',
            updated_at: document.querySelector('[data-user-updated]')?.textContent || ''
        };
        
        if (userData.name) {
            dashboard.updateUserInfo(userData);
        }
    }
});
```

---

## Manejo de Logout

### 1. Logout con confirmación

```javascript
// Función para logout
async function logoutUser() {
    const confirmLogout = confirm('¿Estás seguro de que quieres cerrar sesión?');
    if (!confirmLogout) return;
    
    try {
        const response = await window.authAPI.makeRequest('/logout', {
            method: 'POST',
            body: window.authAPI.objectToFormData({
                _token: window.authAPI.csrfToken
            })
        });
        
        if (response.redirected) {
            // Logout exitoso
            localStorage.setItem('auth_status', 'logged_out'); // Para otras pestañas
            window.location.href = response.url;
        }
        
    } catch (error) {
        console.error('Logout error:', error);
        alert('Error al cerrar sesión. Por favor, inténtalo de nuevo.');
    }
}

// Setup de botones de logout
document.addEventListener('DOMContentLoaded', function() {
    const logoutButtons = document.querySelectorAll('.logout-btn, [data-action="logout"]');
    
    logoutButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            logoutUser();
        });
    });
    
    // Logout automático en otras pestañas
    window.addEventListener('storage', function(e) {
        if (e.key === 'auth_status' && e.newValue === 'logged_out') {
            window.location.href = '/';
        }
    });
});
```

---

## Utilidades Generales

### 1. Manejo de errores del servidor

```javascript
// Función para parsear errores de validación de Laravel
function parseAndShowErrors(htmlResponse) {
    // Si es una respuesta JSON (para APIs futuras)
    try {
        const jsonData = JSON.parse(htmlResponse);
        if (jsonData.errors) {
            showValidationErrors(jsonData.errors);
            return;
        }
    } catch (e) {
        // No es JSON, continuar con parsing HTML
    }
    
    // Parsear HTML para extraer errores
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlResponse, 'text/html');
    const errorElements = doc.querySelectorAll('.invalid-feedback, .alert-danger');
    
    errorElements.forEach(element => {
        showError(element.textContent.trim());
    });
}

// Mostrar errores de validación
function showValidationErrors(errors) {
    Object.keys(errors).forEach(fieldName => {
        const input = document.querySelector(`input[name="${fieldName}"]`);
        if (input && errors[fieldName][0]) {
            showFieldError(input, errors[fieldName][0]);
        }
    });
}

// Funciones de UI para mensajes
function showError(message) {
    showMessage(message, 'error');
}

function showSuccess(message) {
    showMessage(message, 'success');
}

function showMessage(message, type = 'info') {
    // Crear elemento de mensaje
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert alert-${type} alert-dismissible fade show`;
    messageDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar en el DOM
    const container = document.querySelector('.alert-container') || document.body;
    container.insertBefore(messageDiv, container.firstChild);
    
    // Auto-hide después de 5 segundos
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

function clearErrors() {
    // Limpiar errores de campos
    document.querySelectorAll('.is-invalid').forEach(input => {
        input.classList.remove('is-invalid');
    });
    
    document.querySelectorAll('.invalid-feedback').forEach(error => {
        error.style.display = 'none';
    });
    
    // Limpiar mensajes de alerta
    document.querySelectorAll('.alert').forEach(alert => {
        alert.remove();
    });
}
```

### 2. Helpers para formularios

```javascript
// Helpers para trabajar con formularios
class FormHelpers {
    // Serializar formulario a objeto
    static serializeForm(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    }
    
    // Llenar formulario con datos
    static fillForm(form, data) {
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = data[key];
            }
        });
    }
    
    // Limpiar formulario
    static clearForm(form) {
        form.reset();
        clearErrors();
    }
    
    // Deshabilitar formulario
    static disableForm(form) {
        const inputs = form.querySelectorAll('input, button, select, textarea');
        inputs.forEach(input => input.disabled = true);
    }
    
    // Habilitar formulario
    static enableForm(form) {
        const inputs = form.querySelectorAll('input, button, select, textarea');
        inputs.forEach(input => input.disabled = false);
    }
}
```

---

## Configuración de Desarrollo

### 1. Setup para desarrollo local

```javascript
// config/frontend.js
const CONFIG = {
    development: {
        API_BASE_URL: 'http://localhost:8000',
        DEBUG: true,
        TIMEOUT: 10000
    },
    production: {
        API_BASE_URL: window.location.origin,
        DEBUG: false,
        TIMEOUT: 5000
    }
};

// Detectar entorno
const ENV = window.location.hostname === 'localhost' ? 'development' : 'production';
window.APP_CONFIG = CONFIG[ENV];

// Logger para desarrollo
window.log = function(...args) {
    if (window.APP_CONFIG.DEBUG) {
        console.log('[AUTH-API]', ...args);
    }
};
```

### 2. Testing helpers

```javascript
// testing/auth-helpers.js
// Helpers para testing (solo en desarrollo)
if (window.APP_CONFIG.DEBUG) {
    window.authTesting = {
        // Llenar formulario de login automáticamente
        fillLoginForm: (email = 'admin@example.com', password = 'admin123') => {
            const emailInput = document.querySelector('input[name="email"]');
            const passwordInput = document.querySelector('input[name="password"]');
            
            if (emailInput) emailInput.value = email;
            if (passwordInput) passwordInput.value = password;
        },
        
        // Simular submit de login
        submitLogin: () => {
            const form = document.querySelector('#login-form');
            if (form) form.submit();
        },
        
        // Limpiar localStorage
        clearStorage: () => {
            localStorage.clear();
            sessionStorage.clear();
        }
    };
}
```

---

## Integración Completa

### Archivo principal para incluir en layout

```html
<!-- resources/views/layouts/app.blade.php -->
<head>
    <!-- Meta tags existentes -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Styles existentes -->
    @vite(['resources/css/app.css'])
</head>
<body>
    <!-- Contenido -->
    
    <!-- Container para mensajes -->
    <div class="alert-container position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>
    
    <!-- Scripts -->
    @vite(['resources/js/app.js'])
    
    <!-- Auth API -->
    <script>
        // Configuración inline
        window.authConfig = {
            csrfToken: '{{ csrf_token() }}',
            baseURL: '{{ url('/') }}',
            user: @auth {{ Auth::user()->toJson() }} @else null @endauth
        };
    </script>
    
    @stack('scripts')
</body>
```

Estos ejemplos proporcionan una base sólida para que el equipo de frontend implemente rápidamente todas las funcionalidades de autenticación con las mejores prácticas de seguridad y UX.
