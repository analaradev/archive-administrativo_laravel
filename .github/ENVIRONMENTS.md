# 🚀 Configuración de Environments para GitHub Actions

Este documento explica cómo configurar los environments de staging y production en GitHub para que el CI/CD funcione correctamente.

## 📋 Environments Requeridos

### 1. staging
- **Nombre**: `staging`
- **URL**: `https://staging.tu-dominio.com` (reemplazar con tu URL real)
- **Branch**: `dev`
- **Protecciones**: Ninguna (deploy automático)

### 2. production
- **Nombre**: `production`
- **URL**: `https://tu-dominio.com` (reemplazar con tu URL real)
- **Branch**: `main`
- **Protecciones**: 
  - ✅ Requerir revisores (al menos 1)
  - ✅ Esperar timer (opcional: 5 minutos)

## 🔧 Pasos para Configurar

### Paso 1: Crear Environments en GitHub

1. Ve a tu repositorio en GitHub
2. Click en **Settings** → **Environments**
3. Click **New environment**

#### Para Staging:
```
Name: staging
Deployment branches: Selected branches (dev)
Environment secrets: (añadir según necesidad)
Environment variables: (añadir según necesidad)
```

#### Para Production:
```
Name: production
Deployment branches: Selected branches (main)
Environment protection rules:
  ✅ Required reviewers: 1 reviewer
  ✅ Wait timer: 5 minutes (opcional)
Environment secrets: (añadir según necesidad)
Environment variables: (añadir según necesidad)
```

### Paso 2: Configurar Secrets por Environment

#### Staging Secrets:
```
DB_HOST_STAGING=tu-host-staging
DB_PASSWORD_STAGING=tu-password-staging
APP_KEY_STAGING=tu-app-key-staging
```

#### Production Secrets:
```
DB_HOST_PRODUCTION=tu-host-production
DB_PASSWORD_PRODUCTION=tu-password-production
APP_KEY_PRODUCTION=tu-app-key-production
```

### Paso 3: Configurar Branch Protection Rules

#### Para rama `main`:
```
Settings → Branches → Add rule
Branch name pattern: main
✅ Require status checks to pass before merging
✅ Require branches to be up to date before merging
✅ Require pull request reviews before merging
✅ Dismiss stale PR approvals when new commits are pushed
✅ Restrict pushes that create files larger than 100MB
```

#### Para rama `dev`:
```
Settings → Branches → Add rule
Branch name pattern: dev
✅ Require status checks to pass before merging
✅ Require branches to be up to date before merging
✅ Require pull request reviews before merging (1 reviewer)
```

## 🎯 Flujo de Deploy Configurado

### Deploy Automático a Staging
- **Trigger**: Push a rama `dev`
- **Condición**: Todos los tests pasan
- **Destino**: Environment `staging`
- **Protección**: Ninguna (automático)

### Deploy Manual a Production
- **Trigger**: Push a rama `main`
- **Condición**: Todos los tests pasan + Aprobación manual
- **Destino**: Environment `production`
- **Protección**: Requiere 1 revisor + timer opcional

## 📝 Variables de Environment Sugeridas

### Staging
```bash
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.tu-dominio.com
DB_CONNECTION=mysql
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Production
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
DB_CONNECTION=mysql
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 🔍 Verificación de Configuración

Para verificar que todo está configurado correctamente:

1. **Staging**: Haz push a `dev` y verifica que el deploy sea automático
2. **Production**: Haz push a `main` y verifica que requiera aprobación manual

## 🚨 Troubleshooting

### Error: "Environment protection rule" falló
- Verifica que el environment está configurado correctamente
- Revisa que los revisores tengan permisos adecuados

### Error: "Required status check" falló
- Verifica que todos los tests estén pasando
- Revisa los logs del workflow para detalles específicos

### Error: Deploy falló
- Verifica las secrets y variables de environment
- Revisa los scripts de deploy en `/scripts/`
