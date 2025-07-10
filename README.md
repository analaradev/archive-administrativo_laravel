# Sistema Administrativo Laravel

Sistema administrativo desarrollado en Laravel 12 con autenticación completa de usuarios y dashboard.

## Características

- ✅ Sistema de autenticación completo (login/registro/logout)
- ✅ Dashboard administrativo con información del usuario
- ✅ Middleware de autenticación para rutas protegidas
- ✅ Validación de formularios
- ✅ Pruebas unitarias y de feature completas
- ✅ Interfaz responsive y moderna
- ✅ Seeders para datos de prueba

## Tecnologías

- **Laravel 12** - Framework principal
- **MariaDB** - Base de datos
- **Blade Templates** - Motor de plantillas
- **Vite** - Build tool para assets
- **PHPUnit** - Testing framework

## Instalación

### Prerrequisitos

- PHP 8.2+
- Composer
- Node.js y npm
- MariaDB/MySQL

### Pasos de instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/ederjgb94/proyecto-administrativo-laravel.git
   cd proyecto-administrativo-laravel
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   npm install
   ```

3. **Configurar el entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurar la base de datos**
   
   Edita el archivo `.env` con tus credenciales de base de datos:
   ```env
   DB_CONNECTION=mariadb
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=proyectoadministrativo
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Ejecutar migraciones y seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Compilar assets**
   ```bash
   npm run dev
   ```

7. **Iniciar el servidor**
   ```bash
   php artisan serve
   ```

El sistema estará disponible en `http://localhost:8000`

## Usuarios de Prueba

El sistema incluye los siguientes usuarios de prueba:

- **Administrador**
  - Email: `admin@example.com`
  - Contraseña: `admin123`

- **Usuario Test**
  - Email: `test@example.com`
  - Contraseña: `password`

## Estructura del Proyecto

### Controladores
- `App\Http\Controllers\Auth\AuthController` - Maneja login, registro y logout
- `App\Http\Controllers\DashboardController` - Maneja el dashboard principal

### Rutas
- `/` - Página principal (redirige al dashboard si está autenticado)
- `/login` - Formulario de inicio de sesión
- `/register` - Formulario de registro
- `/dashboard` - Dashboard principal (requiere autenticación)
- `/logout` - Cerrar sesión

### Vistas
- `resources/views/layouts/app.blade.php` - Layout principal
- `resources/views/auth/login.blade.php` - Vista de login
- `resources/views/auth/register.blade.php` - Vista de registro
- `resources/views/dashboard.blade.php` - Vista del dashboard

### Modelos
- `App\Models\User` - Modelo de usuario con autenticación

## Testing

El sistema incluye pruebas completas para todas las funcionalidades:

```bash
# Ejecutar todas las pruebas
php artisan test

# Ejecutar pruebas específicas
php artisan test tests/Feature/Auth/
php artisan test tests/Unit/
```

### Cobertura de Pruebas

- **Feature Tests**
  - ✅ Login completo (8 pruebas)
  - ✅ Registro completo (9 pruebas)
  - ✅ Dashboard (5 pruebas)

- **Unit Tests**
  - ✅ Modelo User (6 pruebas)
  - ✅ Controladores (7 pruebas)

Total: **38 pruebas** con **76 aserciones**

## Middleware de Autenticación

- Las rutas del dashboard están protegidas por el middleware `auth`
- Los usuarios no autenticados son redirigidos automáticamente al login
- Las rutas de login/registro están protegidas por el middleware `guest`

## Validaciones

### Login
- Email requerido y válido
- Contraseña requerida
- Credenciales válidas

### Registro
- Nombre requerido (máximo 255 caracteres)
- Email requerido, válido y único
- Contraseña requerida (mínimo 8 caracteres) con confirmación

## Desarrollo

### Comandos útiles

```bash
# Migrar base de datos
php artisan migrate

# Ejecutar seeders
php artisan db:seed

# Limpiar cache
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Ejecutar pruebas
php artisan test

# Generar nueva clave
php artisan key:generate
```

## Próximas Funcionalidades

- [ ] Roles y permisos
- [ ] Gestión de usuarios
- [ ] Panel de configuración
- [ ] Dashboard con métricas avanzadas
- [ ] Sistema de notificaciones
- [ ] API REST
- [ ] Documentación de API

## Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## Soporte

Si tienes algún problema o pregunta, por favor abre un [issue](https://github.com/ederjgb94/proyecto-administrativo-laravel/issues) en GitHub.
