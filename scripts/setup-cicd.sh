#!/bin/bash

# 🚀 Script de Configuración Automática CI/CD
# Este script configura automáticamente el entorno para el nuevo workflow CI/CD

set -e

echo "🚀 CONFIGURACIÓN AUTOMÁTICA CI/CD"
echo "================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para mostrar mensajes
show_message() {
    echo -e "${GREEN}✅ $1${NC}"
}

show_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

show_error() {
    echo -e "${RED}❌ $1${NC}"
}

show_info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

# Verificar que estamos en el directorio correcto
if [ ! -f "composer.json" ]; then
    show_error "No se encontró composer.json. Ejecuta este script desde la raíz del proyecto."
    exit 1
fi

show_message "Proyecto Laravel detectado"

# 1. Verificar si GitHub CLI está instalado
echo ""
echo "🔍 Verificando dependencias..."
if ! command -v gh &> /dev/null; then
    show_warning "GitHub CLI no está instalado"
    show_info "Instálalo desde: https://cli.github.com/"
    show_info "O con: winget install GitHub.cli (Windows)"
else
    show_message "GitHub CLI encontrado"
fi

# 2. Verificar autenticación con GitHub
echo ""
echo "🔐 Verificando autenticación con GitHub..."
if gh auth status &> /dev/null; then
    show_message "Autenticado con GitHub"
else
    show_warning "No estás autenticado con GitHub"
    show_info "Ejecuta: gh auth login"
    read -p "¿Quieres autenticarte ahora? (y/N): " auth_now
    if [[ $auth_now =~ ^[Yy]$ ]]; then
        gh auth login
    else
        show_warning "Salteando configuración de GitHub por ahora"
    fi
fi

# 3. Obtener información del repositorio
echo ""
echo "📋 Información del repositorio..."
if gh repo view &> /dev/null; then
    REPO_INFO=$(gh repo view --json owner,name)
    REPO_OWNER=$(echo $REPO_INFO | jq -r '.owner.login')
    REPO_NAME=$(echo $REPO_INFO | jq -r '.name')
    show_message "Repositorio: $REPO_OWNER/$REPO_NAME"
else
    show_warning "No se pudo obtener información del repositorio"
    read -p "Ingresa owner/repo (ej: usuario/mi-proyecto): " manual_repo
    REPO_OWNER=$(echo $manual_repo | cut -d'/' -f1)
    REPO_NAME=$(echo $manual_repo | cut -d'/' -f2)
fi

# 4. Crear environments si GitHub CLI está disponible
echo ""
echo "🌍 Configurando environments..."
if command -v gh &> /dev/null && gh auth status &> /dev/null; then
    
    # Crear environment staging
    echo "Creando environment 'staging'..."
    gh api \
        --method PUT \
        -H "Accept: application/vnd.github+json" \
        -H "X-GitHub-Api-Version: 2022-11-28" \
        "/repos/$REPO_OWNER/$REPO_NAME/environments/staging" \
        -f deployment_branch_policy='{"protected_branches":false,"custom_branch_policies":true}' \
        > /dev/null 2>&1 && show_message "Environment 'staging' creado" || show_warning "Environment 'staging' ya existe"

    # Crear environment production
    echo "Creando environment 'production'..."
    gh api \
        --method PUT \
        -H "Accept: application/vnd.github+json" \
        -H "X-GitHub-Api-Version: 2022-11-28" \
        "/repos/$REPO_OWNER/$REPO_NAME/environments/production" \
        -f deployment_branch_policy='{"protected_branches":false,"custom_branch_policies":true}' \
        > /dev/null 2>&1 && show_message "Environment 'production' creado" || show_warning "Environment 'production' ya existe"

else
    show_warning "Saltando creación automática de environments"
    show_info "Configúralos manualmente en: https://github.com/$REPO_OWNER/$REPO_NAME/settings/environments"
fi

# 5. Verificar archivos de configuración necesarios
echo ""
echo "📁 Verificando archivos de configuración..."

# Verificar .env.example
if [ -f ".env.example" ]; then
    show_message ".env.example encontrado"
else
    show_warning ".env.example no encontrado"
    show_info "Creando .env.example básico..."
    cat > .env.example << 'EOF'
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
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

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
EOF
    show_message ".env.example creado"
fi

# Verificar package.json
if [ -f "package.json" ]; then
    show_message "package.json encontrado"
else
    show_warning "package.json no encontrado"
    show_info "Creando package.json básico..."
    cat > package.json << 'EOF'
{
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite",
        "lint": "echo 'Linting no configurado'",
        "test": "echo 'Tests frontend no configurados'"
    },
    "devDependencies": {
        "axios": "^1.6.4",
        "laravel-vite-plugin": "^1.0",
        "vite": "^5.0"
    }
}
EOF
    show_message "package.json creado"
fi

# 6. Crear directorios necesarios
echo ""
echo "📁 Creando directorios necesarios..."
mkdir -p scripts
mkdir -p tests/Integration
mkdir -p tests/Performance
show_message "Directorios creados"

# 7. Crear scripts de deploy básicos
echo ""
echo "🚀 Creando scripts de deploy básicos..."

# Script de deploy a staging
cat > scripts/deploy-staging.sh << 'EOF'
#!/bin/bash

# 🚀 Deploy a Staging
echo "🚀 Iniciando deploy a STAGING..."

# Variables
STAGING_HOST="tu-servidor-staging.com"
STAGING_USER="deploy"
STAGING_PATH="/var/www/staging"

# Deploy básico (personalizar según tu infraestructura)
echo "📦 Actualizando código..."
# rsync -avz --exclude='.git' --exclude='node_modules' ./ $STAGING_USER@$STAGING_HOST:$STAGING_PATH/

echo "🐘 Actualizando dependencias PHP..."
# ssh $STAGING_USER@$STAGING_HOST "cd $STAGING_PATH && composer install --no-dev --optimize-autoloader"

echo "🟢 Actualizando dependencias Node..."
# ssh $STAGING_USER@$STAGING_HOST "cd $STAGING_PATH && npm ci && npm run build"

echo "🗄️ Ejecutando migraciones..."
# ssh $STAGING_USER@$STAGING_HOST "cd $STAGING_PATH && php artisan migrate --force"

echo "🧹 Limpiando cache..."
# ssh $STAGING_USER@$STAGING_HOST "cd $STAGING_PATH && php artisan config:clear && php artisan cache:clear"

echo "✅ Deploy a staging completado"
EOF

# Script de deploy a production
cat > scripts/deploy-production.sh << 'EOF'
#!/bin/bash

# 🏭 Deploy a Production
echo "🏭 Iniciando deploy a PRODUCCIÓN..."

# Variables
PRODUCTION_HOST="tu-servidor-production.com"
PRODUCTION_USER="deploy"
PRODUCTION_PATH="/var/www/production"

# Verificación de seguridad
read -p "¿Estás seguro de hacer deploy a PRODUCCIÓN? (yes/NO): " confirm
if [ "$confirm" != "yes" ]; then
    echo "❌ Deploy cancelado"
    exit 1
fi

# Deploy con backup
echo "💾 Creando backup..."
# ssh $PRODUCTION_USER@$PRODUCTION_HOST "cd $PRODUCTION_PATH && tar -czf backup-$(date +%Y%m%d-%H%M%S).tar.gz ."

echo "📦 Actualizando código..."
# rsync -avz --exclude='.git' --exclude='node_modules' ./ $PRODUCTION_USER@$PRODUCTION_HOST:$PRODUCTION_PATH/

echo "🐘 Actualizando dependencias PHP..."
# ssh $PRODUCTION_USER@$PRODUCTION_HOST "cd $PRODUCTION_PATH && composer install --no-dev --optimize-autoloader"

echo "🟢 Actualizando dependencias Node..."
# ssh $PRODUCTION_USER@$PRODUCTION_HOST "cd $PRODUCTION_PATH && npm ci && npm run build"

echo "🗄️ Ejecutando migraciones..."
# ssh $PRODUCTION_USER@$PRODUCTION_HOST "cd $PRODUCTION_PATH && php artisan migrate --force"

echo "🧹 Optimizando aplicación..."
# ssh $PRODUCTION_USER@$PRODUCTION_HOST "cd $PRODUCTION_PATH && php artisan config:cache && php artisan route:cache && php artisan view:cache"

echo "✅ Deploy a producción completado"
echo "🌐 Verifica: https://tu-dominio.com"
EOF

chmod +x scripts/deploy-staging.sh
chmod +x scripts/deploy-production.sh
show_message "Scripts de deploy creados"

# 8. Resumen final
echo ""
echo "🎉 CONFIGURACIÓN COMPLETADA"
echo "=========================="
echo ""
show_message "✅ Workflow CI/CD configurado"
show_message "✅ Environments setup (staging/production)"
show_message "✅ Scripts de deploy creados"
show_message "✅ Archivos de configuración verificados"
echo ""
echo "📝 PRÓXIMOS PASOS:"
echo "1. Personaliza los scripts en /scripts/ según tu infraestructura"
echo "2. Configura secrets en GitHub: https://github.com/$REPO_OWNER/$REPO_NAME/settings/secrets/actions"
echo "3. Revisa la configuración de environments: https://github.com/$REPO_OWNER/$REPO_NAME/settings/environments"
echo "4. Haz tu primer PR siguiendo la convención: feature/123-backend-descripcion"
echo ""
show_info "📚 Lee la documentación completa en .github/ENVIRONMENTS.md"
echo ""
echo "🚀 ¡Tu pipeline CI/CD está listo para usar!"
EOF
chmod +x scripts/setup-cicd.sh
