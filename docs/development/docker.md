# 🐳 Guía de Docker

## 📋 Índice
1. [Configuración Inicial](#configuración-inicial)
2. [Desarrollo Local](#desarrollo-local)
3. [Producción](#producción)
4. [Comandos Útiles](#comandos-útiles)
5. [Troubleshooting](#troubleshooting)
6. [Optimización](#optimización)

## 🚀 Configuración Inicial

### Prerequisitos
```bash
# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Instalar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.21.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verificar instalación
docker --version
docker-compose --version
```

### Estructura de Archivos Docker
```
proyecto-administrativo-laravel/
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf
│   │   └── ssl/
│   ├── php/
│   │   ├── Dockerfile
│   │   ├── local.ini
│   │   └── xdebug.ini
│   └── mysql/
│       └── my.cnf
├── docker-compose.yml
├── docker-compose.prod.yml
└── Dockerfile
```

## 🧪 Desarrollo Local

### docker-compose.yml (Development)
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
      target: development
    container_name: proyecto-admin-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - .:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
      - ./docker/php/xdebug.ini:/usr/local/etc/php/conf.d/xdebug.ini
    environment:
      - PHP_IDE_CONFIG=serverName=proyecto-admin
    networks:
      - proyecto-admin
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    container_name: proyecto-admin-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - .:/var/www
      - ./docker/nginx/nginx.conf:/etc/nginx/conf.d/default.conf
      - ./docker/nginx/ssl:/etc/ssl/certs
    networks:
      - proyecto-admin
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: proyecto-admin-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: proyecto_admin
      MYSQL_ROOT_PASSWORD: root
      MYSQL_PASSWORD: secret
      MYSQL_USER: proyecto_user
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/conf.d/my.cnf
    ports:
      - "3306:3306"
    networks:
      - proyecto-admin

  redis:
    image: redis:alpine
    container_name: proyecto-admin-redis
    restart: unless-stopped
    command: redis-server --appendonly yes --requirepass "redis_password"
    volumes:
      - redis_data:/data
    ports:
      - "6379:6379"
    networks:
      - proyecto-admin

  mailhog:
    image: mailhog/mailhog
    container_name: proyecto-admin-mailhog
    restart: unless-stopped
    ports:
      - "1025:1025"
      - "8025:8025"
    networks:
      - proyecto-admin

  node:
    image: node:18-alpine
    container_name: proyecto-admin-node
    working_dir: /var/www
    volumes:
      - .:/var/www
    command: sh -c "npm install && npm run dev"
    ports:
      - "5173:5173"
    networks:
      - proyecto-admin

volumes:
  mysql_data:
  redis_data:

networks:
  proyecto-admin:
    driver: bridge
```

### Dockerfile para PHP (Multi-stage)
```dockerfile
# docker/php/Dockerfile

# Base stage
FROM php:8.2-fpm as base

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libicu-dev \
    zip \
    unzip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar usuario no-root
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

# Development stage
FROM base as development

# Instalar Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Configurar directorio de trabajo
WORKDIR /var/www

# Cambiar al usuario www
USER www

# Production stage
FROM base as production

# Configurar OPcache para producción
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Configurar directorio de trabajo
WORKDIR /var/www

# Copiar archivos del proyecto
COPY --chown=www:www . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configurar permisos
RUN chown -R www:www /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Cambiar al usuario www
USER www

# Exponer puerto
EXPOSE 9000

CMD ["php-fpm"]
```

### Configuración PHP para Desarrollo
```ini
; docker/php/local.ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 512M
max_execution_time = 300
max_input_vars = 3000

; Configuración de logging
log_errors = On
error_log = /var/log/php_errors.log
display_errors = On
display_startup_errors = On
error_reporting = E_ALL

; Configuración de sesiones
session.gc_maxlifetime = 3600
session.save_path = "/tmp"
```

### Configuración Xdebug
```ini
; docker/php/xdebug.ini
[xdebug]
xdebug.mode = debug,develop,coverage
xdebug.client_host = host.docker.internal
xdebug.client_port = 9003
xdebug.start_with_request = yes
xdebug.idekey = VSCODE
xdebug.log = /var/log/xdebug.log
xdebug.log_level = 0
```

### Configuración Nginx
```nginx
# docker/nginx/nginx.conf
server {
    listen 80;
    server_name localhost;
    root /var/www/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    # Logging
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeouts
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Assets con cache para desarrollo
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1h;
        add_header Cache-Control "public";
    }
}
```

### Configuración MySQL
```ini
# docker/mysql/my.cnf
[mysqld]
# Configuración de memoria
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_lock_wait_timeout = 120

# Configuración de conexiones
max_connections = 100
connect_timeout = 60
wait_timeout = 28800

# Configuración de queries
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# Configuración de caracteres
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

[mysql]
default-character-set = utf8mb4

[client]
default-character-set = utf8mb4
```

## 🏭 Producción

### docker-compose.prod.yml
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
      target: production
    container_name: proyecto-admin-app-prod
    restart: always
    working_dir: /var/www
    volumes:
      - storage_data:/var/www/storage
      - ./docker/php/production.ini:/usr/local/etc/php/conf.d/production.ini
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    networks:
      - proyecto-admin
    depends_on:
      - mysql
      - redis
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'

  nginx:
    image: nginx:alpine
    container_name: proyecto-admin-nginx-prod
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./public:/var/www/public:ro
      - ./docker/nginx/nginx.prod.conf:/etc/nginx/conf.d/default.conf:ro
      - ./docker/nginx/ssl:/etc/ssl/certs:ro
      - nginx_logs:/var/log/nginx
    networks:
      - proyecto-admin
    depends_on:
      - app
    deploy:
      resources:
        limits:
          memory: 128M
          cpus: '0.25'

  mysql:
    image: mysql:8.0
    container_name: proyecto-admin-mysql-prod
    restart: always
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/my.prod.cnf:/etc/mysql/conf.d/my.cnf:ro
      - mysql_logs:/var/log/mysql
    networks:
      - proyecto-admin
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '1.0'

  redis:
    image: redis:alpine
    container_name: proyecto-admin-redis-prod
    restart: always
    command: redis-server --appendonly yes --requirepass "${REDIS_PASSWORD}"
    volumes:
      - redis_data:/data
    networks:
      - proyecto-admin
    deploy:
      resources:
        limits:
          memory: 256M
          cpus: '0.25'

  supervisor:
    build:
      context: .
      dockerfile: docker/supervisor/Dockerfile
    container_name: proyecto-admin-supervisor
    restart: always
    volumes:
      - ./docker/supervisor/supervisord.conf:/etc/supervisor/conf.d/supervisord.conf:ro
      - supervisor_logs:/var/log/supervisor
    networks:
      - proyecto-admin
    depends_on:
      - app
      - mysql
      - redis

volumes:
  mysql_data:
  redis_data:
  storage_data:
  nginx_logs:
  mysql_logs:
  supervisor_logs:

networks:
  proyecto-admin:
    driver: bridge
```

### Configuración PHP para Producción
```ini
; docker/php/production.ini
; Configuración optimizada para producción
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 256M
max_execution_time = 60
max_input_vars = 1000

; Seguridad
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
error_reporting = E_ERROR | E_WARNING | E_PARSE

; OPcache (se configura en Dockerfile)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
opcache.validate_timestamps = 0

; Sesiones
session.gc_maxlifetime = 1440
session.save_path = "/tmp"
session.cookie_secure = 1
session.cookie_httponly = 1
session.use_strict_mode = 1
```

### Supervisor para Queues
```dockerfile
# docker/supervisor/Dockerfile
FROM php:8.2-cli

RUN apt-get update && apt-get install -y supervisor

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

```ini
; docker/supervisor/supervisord.conf
[unix_http_server]
file=/var/run/supervisor.sock
chmod=0700

[supervisord]
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid
childlogdir=/var/log/supervisor

[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[supervisorctl]
serverurl=unix:///var/run/supervisor.sock

[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/worker.log
stopwaitsecs=3600

[program:laravel-scheduler]
process_name=%(program_name)s
command=php /var/www/artisan schedule:work
directory=/var/www
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/scheduler.log
```

## 🛠️ Comandos Útiles

### Scripts de Desarrollo
```bash
#!/bin/bash
# scripts/dev-setup.sh

echo "🚀 Configurando entorno de desarrollo..."

# Construir y levantar contenedores
docker-compose up -d --build

# Esperar a que MySQL esté listo
echo "⏳ Esperando a MySQL..."
until docker-compose exec mysql mysqladmin ping -h"localhost" --silent; do
    sleep 1
done

# Instalar dependencias
echo "📦 Instalando dependencias..."
docker-compose exec app composer install
docker-compose exec node npm install

# Configurar Laravel
echo "🔧 Configurando Laravel..."
docker-compose exec app cp .env.example .env
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed

# Build assets
echo "🎨 Compilando assets..."
docker-compose exec node npm run dev

echo "✅ Entorno de desarrollo listo!"
echo "🌐 Aplicación disponible en: http://localhost"
echo "📧 MailHog disponible en: http://localhost:8025"
```

```bash
#!/bin/bash
# scripts/dev-reset.sh

echo "🔄 Reseteando entorno de desarrollo..."

# Parar contenedores
docker-compose down

# Eliminar volúmenes
docker-compose down -v

# Eliminar imágenes
docker-compose down --rmi all

# Limpiar todo
docker system prune -f

echo "✅ Entorno reseteado completamente"
```

### Comandos de Desarrollo Frecuentes
```bash
# Levantar entorno
docker-compose up -d

# Ver logs en tiempo real
docker-compose logs -f app

# Ejecutar comandos Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan make:controller TestController

# Ejecutar tests
docker-compose exec app php artisan test

# Acceder al contenedor
docker-compose exec app bash

# Instalar dependencias
docker-compose exec app composer install
docker-compose exec node npm install

# Watch assets
docker-compose exec node npm run dev

# Backup de base de datos
docker-compose exec mysql mysqldump -u root -proot proyecto_admin > backup.sql

# Restaurar base de datos
docker-compose exec -T mysql mysql -u root -proot proyecto_admin < backup.sql

# Ver procesos activos
docker-compose ps

# Verificar recursos
docker stats

# Limpiar logs
docker-compose exec app php artisan log:clear
```

## 🔧 Troubleshooting

### Problemas Comunes

#### 1. Contenedor no inicia
```bash
# Ver logs detallados
docker-compose logs app

# Verificar configuración
docker-compose config

# Reconstruir imagen
docker-compose build --no-cache app
```

#### 2. Permisos de archivos
```bash
# Arreglar permisos desde dentro del contenedor
docker-compose exec app chown -R www-data:www-data /var/www
docker-compose exec app chmod -R 755 /var/www/storage
docker-compose exec app chmod -R 755 /var/www/bootstrap/cache

# O desde el host (Linux/Mac)
sudo chown -R $USER:$USER .
chmod -R 755 storage bootstrap/cache
```

#### 3. Base de datos no conecta
```bash
# Verificar que MySQL está ejecutándose
docker-compose ps mysql

# Verificar logs de MySQL
docker-compose logs mysql

# Conectar manualmente
docker-compose exec mysql mysql -u root -proot

# Verificar variables de entorno
docker-compose exec app env | grep DB_
```

#### 4. Assets no se cargan
```bash
# Verificar que Node está corriendo
docker-compose ps node

# Reconstruir assets
docker-compose exec node npm run build

# Limpiar cache de Vite
docker-compose exec node npm run dev -- --force
```

#### 5. Xdebug no funciona
```bash
# Verificar configuración
docker-compose exec app php -m | grep xdebug

# Ver configuración Xdebug
docker-compose exec app php --ini | grep xdebug

# Verificar logs
docker-compose exec app tail -f /var/log/xdebug.log
```

### Script de Diagnóstico
```bash
#!/bin/bash
# scripts/diagnose.sh

echo "🔍 Diagnóstico del entorno Docker..."

echo "📊 Estado de contenedores:"
docker-compose ps

echo -e "\n💾 Uso de memoria:"
docker stats --no-stream

echo -e "\n🗄️ Espacio en disco:"
df -h

echo -e "\n🐳 Imágenes Docker:"
docker images

echo -e "\n📦 Volúmenes:"
docker volume ls

echo -e "\n🌐 Redes:"
docker network ls

echo -e "\n🔍 Variables de entorno de la app:"
docker-compose exec app env | grep -E "(APP_|DB_|CACHE_|SESSION_)"

echo -e "\n📝 Logs recientes de la aplicación:"
docker-compose logs --tail=20 app

echo -e "\n✅ Diagnóstico completado"
```

## ⚡ Optimización

### Optimización de Imágenes
```dockerfile
# Ejemplo de optimización multi-stage más eficiente

# Build stage
FROM node:18-alpine as node-build
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY resources/ resources/
COPY vite.config.js ./
RUN npm run build

# PHP Production
FROM php:8.2-fpm-alpine as production

# Instalar solo las extensiones necesarias
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql opcache \
    && apk del --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev

# Copiar solo lo necesario
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .
COPY --from=node-build /app/public/build public/build

RUN composer run-script post-autoload-dump

USER www-data
```

### docker-compose.override.yml para Desarrollo
```yaml
# docker-compose.override.yml (auto-loaded)
version: '3.8'

services:
  app:
    volumes:
      - .:/var/www:cached
    environment:
      - XDEBUG_CONFIG=remote_host=host.docker.internal

  nginx:
    volumes:
      - .:/var/www:cached
```

### Configuración de .dockerignore
```
# .dockerignore
.git
.github
node_modules
vendor
tests
storage/logs
storage/framework/cache
storage/framework/sessions
storage/framework/views
.env
.env.*
*.log
.DS_Store
Thumbs.db
```

### Health Checks
```yaml
# En docker-compose.yml
services:
  app:
    healthcheck:
      test: ["CMD", "php", "-m"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  mysql:
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s

  nginx:
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost"]
      interval: 30s
      timeout: 10s
      retries: 3
```

---

## 📚 Recursos Adicionales

- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [Laravel Docker Configuration](https://laravel.com/docs/sail)
- [PHP-FPM Configuration](https://www.php.net/manual/en/install.fpm.configuration.php)
- [Nginx Docker Guide](https://hub.docker.com/_/nginx)
