# 🚀 Guía de Deployment

## 📋 Índice
1. [Prerequisitos](#prerequisitos)
2. [Deployment en Desarrollo](#deployment-en-desarrollo)
3. [Deployment en Staging](#deployment-en-staging)
4. [Deployment en Producción](#deployment-en-producción)
5. [Docker Deployment](#docker-deployment)
6. [Variables de Entorno](#variables-de-entorno)
7. [Rollback Strategy](#rollback-strategy)
8. [Monitoring](#monitoring)

## 🔧 Prerequisitos

### Sistema Operativo
- **Ubuntu 20.04+ / CentOS 8+** (recomendado)
- **Windows Server 2019+** (alternativo)

### Software Requerido
```bash
# Servidor Web
nginx >= 1.18
# o
apache2 >= 2.4

# Base de Datos
mysql >= 8.0
# o
mariadb >= 10.5

# PHP
php >= 8.2
php-fpm
php-mysql
php-xml
php-curl
php-mbstring
php-gd
php-zip
php-bcmath
php-intl

# Herramientas
composer >= 2.0
nodejs >= 18.0
npm >= 9.0
git
```

### Recursos Mínimos del Servidor
- **CPU**: 2 cores mínimo, 4 cores recomendado
- **RAM**: 4GB mínimo, 8GB recomendado
- **Storage**: 20GB mínimo, SSD recomendado
- **Network**: 100Mbps mínimo

## 🧪 Deployment en Desarrollo

### Setup Local
```bash
# 1. Clonar repositorio
git clone https://github.com/usuario/proyecto-administrativo-laravel.git
cd proyecto-administrativo-laravel

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias Node.js
npm install

# 4. Configurar entorno
cp .env.example .env
php artisan key:generate

# 5. Configurar base de datos
# Editar .env con credenciales de DB local

# 6. Migrar base de datos
php artisan migrate
php artisan db:seed

# 7. Build assets
npm run dev

# 8. Levantar servidor de desarrollo
php artisan serve
```

### Configuración Local (.env.local)
```bash
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proyecto_admin_local
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_MAILER=log
```

## 🔬 Deployment en Staging

### Configuración del Servidor Staging
```bash
# 1. Actualizar sistema
sudo apt update && sudo apt upgrade -y

# 2. Instalar stack LEMP
sudo apt install nginx mysql-server php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring php8.2-gd php8.2-zip php8.2-bcmath php8.2-intl -y

# 3. Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 4. Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### Configuración Nginx para Staging
```nginx
server {
    listen 80;
    server_name staging.proyecto-admin.com;
    root /var/www/proyecto-administrativo-laravel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Script de Deployment Staging
```bash
#!/bin/bash
# deploy-staging.sh

set -e

echo "🚀 Iniciando deployment en Staging..."

# Variables
DEPLOY_PATH="/var/www/proyecto-administrativo-laravel"
BACKUP_PATH="/var/backups/proyecto-admin"
BRANCH="develop"

# 1. Crear backup
echo "📦 Creando backup..."
sudo mkdir -p $BACKUP_PATH
sudo tar -czf $BACKUP_PATH/backup-$(date +%Y%m%d_%H%M%S).tar.gz $DEPLOY_PATH

# 2. Actualizar código
echo "📥 Actualizando código..."
cd $DEPLOY_PATH
git fetch origin
git checkout $BRANCH
git pull origin $BRANCH

# 3. Instalar dependencias
echo "📦 Instalando dependencias..."
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 4. Configurar permisos
echo "🔐 Configurando permisos..."
sudo chown -R www-data:www-data $DEPLOY_PATH
sudo chmod -R 755 $DEPLOY_PATH
sudo chmod -R 775 $DEPLOY_PATH/storage
sudo chmod -R 775 $DEPLOY_PATH/bootstrap/cache

# 5. Ejecutar migraciones
echo "🗄️ Ejecutando migraciones..."
php artisan migrate --force

# 6. Limpiar cache
echo "🧹 Limpiando cache..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 7. Optimizar aplicación
echo "⚡ Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Reiniciar servicios
echo "🔄 Reiniciando servicios..."
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm

echo "✅ Deployment completado exitosamente!"
```

## 🏭 Deployment en Producción

### Configuración del Servidor Producción
```bash
# Configuración adicional para producción
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'

# Configurar fail2ban
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Instalar Certbot para SSL
sudo apt install certbot python3-certbot-nginx -y
```

### Configuración Nginx para Producción
```nginx
server {
    listen 443 ssl http2;
    server_name proyecto-admin.com www.proyecto-admin.com;
    root /var/www/proyecto-administrativo-laravel/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/proyecto-admin.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/proyecto-admin.com/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/proyecto-admin.com/chain.pem;

    # Security headers
    add_header X-Frame-Options "DENY";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';";

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    index index.php;
    charset utf-8;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Login rate limiting
    location /login {
        limit_req zone=login burst=3 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name proyecto-admin.com www.proyecto-admin.com;
    return 301 https://$server_name$request_uri;
}
```

### Script de Deployment Producción
```bash
#!/bin/bash
# deploy-production.sh

set -e

echo "🏭 Iniciando deployment en PRODUCCIÓN..."

# Variables
DEPLOY_PATH="/var/www/proyecto-administrativo-laravel"
BACKUP_PATH="/var/backups/proyecto-admin"
BRANCH="main"
SLACK_WEBHOOK_URL="https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK"

# Función para notificar Slack
notify_slack() {
    local message="$1"
    local color="$2"
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"$message\", \"color\":\"$color\"}" \
        $SLACK_WEBHOOK_URL
}

# Función de rollback
rollback() {
    echo "❌ Error detectado. Ejecutando rollback..."
    notify_slack "🚨 Error en deployment de producción. Ejecutando rollback..." "danger"
    
    # Restaurar último backup
    LATEST_BACKUP=$(ls -t $BACKUP_PATH/*.tar.gz | head -n1)
    sudo tar -xzf $LATEST_BACKUP -C /var/www/
    
    # Reiniciar servicios
    sudo systemctl restart nginx
    sudo systemctl restart php8.2-fpm
    
    notify_slack "🔄 Rollback completado. Sistema restaurado." "warning"
    exit 1
}

# Configurar trap para errores
trap rollback ERR

notify_slack "🚀 Iniciando deployment en producción..." "good"

# 1. Modo mantenimiento
echo "🚧 Activando modo mantenimiento..."
cd $DEPLOY_PATH
php artisan down --message="Sistema en mantenimiento. Volvemos pronto."

# 2. Crear backup
echo "📦 Creando backup completo..."
sudo mkdir -p $BACKUP_PATH
sudo tar -czf $BACKUP_PATH/backup-$(date +%Y%m%d_%H%M%S).tar.gz $DEPLOY_PATH
# Mantener solo los últimos 10 backups
sudo find $BACKUP_PATH -name "backup-*.tar.gz" -type f | sort -r | tail -n +11 | xargs sudo rm -f

# 3. Actualizar código
echo "📥 Actualizando código..."
git fetch origin
git checkout $BRANCH
git pull origin $BRANCH

# 4. Instalar dependencias
echo "📦 Instalando dependencias..."
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --only=production
npm run build

# 5. Configurar permisos
echo "🔐 Configurando permisos..."
sudo chown -R www-data:www-data $DEPLOY_PATH
sudo chmod -R 755 $DEPLOY_PATH
sudo chmod -R 775 $DEPLOY_PATH/storage
sudo chmod -R 775 $DEPLOY_PATH/bootstrap/cache

# 6. Ejecutar migraciones
echo "🗄️ Ejecutando migraciones..."
php artisan migrate --force

# 7. Limpiar y optimizar cache
echo "🧹 Optimizando aplicación..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Ejecutar tests críticos
echo "🧪 Ejecutando tests críticos..."
php artisan test --testsuite=Critical --stop-on-failure

# 9. Verificar aplicación
echo "🔍 Verificando aplicación..."
response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost)
if [ $response -ne 200 ]; then
    echo "❌ La aplicación no responde correctamente"
    exit 1
fi

# 10. Reiniciar servicios
echo "🔄 Reiniciando servicios..."
sudo systemctl reload nginx
sudo systemctl restart php8.2-fpm

# 11. Desactivar modo mantenimiento
echo "✅ Desactivando modo mantenimiento..."
php artisan up

# 12. Verificación post-deployment
echo "🔍 Verificación final..."
sleep 5
response=$(curl -s -o /dev/null -w "%{http_code}" https://proyecto-admin.com)
if [ $response -eq 200 ]; then
    echo "✅ Deployment completado exitosamente!"
    notify_slack "✅ Deployment de producción completado exitosamente!" "good"
else
    echo "❌ Error en verificación final"
    exit 1
fi

# Limpiar trap
trap - ERR
```

## 🐳 Docker Deployment

### Dockerfile
```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm

# Instalar extensiones PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader
RUN npm ci --only=production
RUN npm run build

# Configurar permisos
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage
RUN chmod -R 755 /var/www/bootstrap/cache

# Exponer puerto
EXPOSE 9000

CMD ["php-fpm"]
```

### docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: proyecto-admin-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - .:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - proyecto-admin

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

  mysql:
    image: mysql:8.0
    container_name: proyecto-admin-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: proyecto_admin
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_PASSWORD: user_password
      MYSQL_USER: proyecto_user
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - proyecto-admin

  redis:
    image: redis:alpine
    container_name: proyecto-admin-redis
    restart: unless-stopped
    volumes:
      - redis_data:/data
    networks:
      - proyecto-admin

volumes:
  mysql_data:
  redis_data:

networks:
  proyecto-admin:
    driver: bridge
```

### Comandos Docker
```bash
# Construir y levantar contenedores
docker-compose up -d --build

# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Ver logs
docker-compose logs -f app

# Entrar al contenedor
docker-compose exec app bash

# Parar contenedores
docker-compose down

# Parar y eliminar volúmenes
docker-compose down -v
```

## 🔐 Variables de Entorno

### Configuración por Ambiente

#### .env.production
```bash
APP_NAME="Proyecto Administrativo"
APP_ENV=production
APP_KEY=base64:GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://proyecto-admin.com

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=proyecto_admin_prod
DB_USERNAME=proyecto_user
DB_PASSWORD=SECURE_PASSWORD_HERE

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=REDIS_PASSWORD_HERE
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=MAIL_USERNAME_HERE
MAIL_PASSWORD=MAIL_PASSWORD_HERE
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@proyecto-admin.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

# Security
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS="proyecto-admin.com"
```

### Script de Configuración de Variables
```bash
#!/bin/bash
# setup-env.sh

environment=$1

if [ -z "$environment" ]; then
    echo "Uso: ./setup-env.sh [local|staging|production]"
    exit 1
fi

case $environment in
    "local")
        cp .env.example .env
        echo "✅ Configuración local aplicada"
        ;;
    "staging")
        cp .env.staging .env
        echo "✅ Configuración staging aplicada"
        ;;
    "production")
        cp .env.production .env
        echo "✅ Configuración producción aplicada"
        ;;
    *)
        echo "❌ Ambiente no válido. Use: local, staging, o production"
        exit 1
        ;;
esac

# Generar clave de aplicación
php artisan key:generate

echo "🔑 Clave de aplicación generada"
echo "📝 Recuerda configurar las variables específicas de tu entorno"
```

## 🔄 Rollback Strategy

### Automated Rollback Script
```bash
#!/bin/bash
# rollback.sh

BACKUP_PATH="/var/backups/proyecto-admin"
DEPLOY_PATH="/var/www/proyecto-administrativo-laravel"

echo "🔄 Iniciando rollback..."

# Listar backups disponibles
echo "📦 Backups disponibles:"
ls -la $BACKUP_PATH/*.tar.gz | nl

# Seleccionar backup
read -p "Selecciona el número del backup a restaurar: " backup_number
backup_file=$(ls -t $BACKUP_PATH/*.tar.gz | sed -n "${backup_number}p")

if [ -z "$backup_file" ]; then
    echo "❌ Backup no válido"
    exit 1
fi

echo "📦 Restaurando backup: $backup_file"

# Activar modo mantenimiento
cd $DEPLOY_PATH
php artisan down

# Crear backup del estado actual
sudo tar -czf $BACKUP_PATH/pre-rollback-$(date +%Y%m%d_%H%M%S).tar.gz $DEPLOY_PATH

# Restaurar backup seleccionado
sudo tar -xzf $backup_file -C /var/www/

# Configurar permisos
sudo chown -R www-data:www-data $DEPLOY_PATH
sudo chmod -R 755 $DEPLOY_PATH
sudo chmod -R 775 $DEPLOY_PATH/storage
sudo chmod -R 775 $DEPLOY_PATH/bootstrap/cache

# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Reiniciar servicios
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm

# Desactivar modo mantenimiento
php artisan up

echo "✅ Rollback completado exitosamente"
```

## 📊 Monitoring

### Health Check Script
```bash
#!/bin/bash
# health-check.sh

check_service() {
    local service=$1
    if systemctl is-active --quiet $service; then
        echo "✅ $service está ejecutándose"
    else
        echo "❌ $service no está ejecutándose"
        sudo systemctl restart $service
    fi
}

check_url() {
    local url=$1
    local expected_code=$2
    local response=$(curl -s -o /dev/null -w "%{http_code}" $url)
    
    if [ $response -eq $expected_code ]; then
        echo "✅ $url responde correctamente ($response)"
    else
        echo "❌ $url responde con código $response (esperado: $expected_code)"
    fi
}

check_disk_space() {
    local threshold=80
    local usage=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
    
    if [ $usage -lt $threshold ]; then
        echo "✅ Espacio en disco: ${usage}%"
    else
        echo "⚠️ Espacio en disco: ${usage}% (threshold: ${threshold}%)"
    fi
}

echo "🔍 Verificando estado del sistema..."

# Verificar servicios
check_service nginx
check_service php8.2-fpm
check_service mysql

# Verificar URLs
check_url "https://proyecto-admin.com" 200
check_url "https://proyecto-admin.com/login" 200

# Verificar recursos
check_disk_space

# Verificar base de datos
mysql -u root -p$MYSQL_ROOT_PASSWORD -e "SELECT 1" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Base de datos accesible"
else
    echo "❌ Error conectando a base de datos"
fi

echo "🏁 Verificación completada"
```

### Cron Jobs para Monitoreo
```bash
# Agregar a crontab
# crontab -e

# Health check cada 5 minutos
*/5 * * * * /var/scripts/health-check.sh >> /var/log/health-check.log 2>&1

# Backup automático diario a las 2 AM
0 2 * * * /var/scripts/backup-daily.sh >> /var/log/backup.log 2>&1

# Limpiar logs antiguos semanalmente
0 3 * * 0 find /var/log -name "*.log" -mtime +30 -delete

# Actualizar certificados SSL mensualmente
0 4 1 * * certbot renew --quiet && systemctl reload nginx
```

## 🚨 Troubleshooting

### Problemas Comunes

#### 1. Error 500 - Internal Server Error
```bash
# Verificar logs de error
tail -f /var/log/nginx/error.log
tail -f storage/logs/laravel.log

# Verificar permisos
sudo chown -R www-data:www-data /var/www/proyecto-administrativo-laravel
sudo chmod -R 755 storage bootstrap/cache

# Limpiar cache
php artisan cache:clear
php artisan config:clear
```

#### 2. Error de Base de Datos
```bash
# Verificar conexión
mysql -u username -p database_name

# Verificar configuración
php artisan tinker
>>> DB::connection()->getPdo();

# Verificar migraciones
php artisan migrate:status
```

#### 3. Performance Issues
```bash
# Verificar procesos
top
htop

# Verificar memoria
free -h

# Verificar queries lentas
mysql -e "SHOW FULL PROCESSLIST;"

# Verificar cache
php artisan cache:clear
redis-cli FLUSHALL
```

---

## 📚 Recursos Adicionales

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Nginx Configuration Best Practices](https://nginx.org/en/docs/)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [PHP Performance Tips](https://www.php.net/manual/en/features.performance.php)
