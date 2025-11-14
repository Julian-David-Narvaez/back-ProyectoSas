# 🚂 Guía Rápida de Despliegue en Railway

## 📋 Resumen Rápido

Tu contraseña de aplicación de Gmail: `xxgxjcupzmnxadxj`

---

## 🚀 Pasos para Desplegar

### 1️⃣ Preparar el Proyecto

```bash
cd c:\laragon\www\back-ProyectoSas\backend

# Verificar que .env NO está en git
git status

# Si aparece .env, asegúrate de que esté en .gitignore
echo .env >> .gitignore
```

### 2️⃣ Crear Proyecto en Railway

1. Ve a: https://railway.app/
2. Login con GitHub
3. Click en "New Project"
4. Selecciona "Deploy from GitHub repo"
5. Busca y selecciona: `back-ProyectoSas`
6. Railway comenzará a desplegar automáticamente

### 3️⃣ Agregar Base de Datos MySQL

1. En tu proyecto de Railway, click "New" → "Database" → "Add MySQL"
2. Railway creará la base de datos y generará credenciales automáticamente
3. Las variables `MYSQL_*` se agregarán automáticamente

### 4️⃣ Configurar Variables de Entorno

En Railway → Tu Proyecto → Variables → Raw Editor, pega esto:

```env
APP_NAME=Sistema de Citas
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:IELATxnf9G3fGLgqR4uhva8YnLtnoUVGavZOJwZw2vY=
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}
APP_TIMEZONE=America/Bogota

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=julian2002narvaez@gmail.com
MAIL_PASSWORD=xxgxjcupzmnxadxj
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=julian2002narvaez@gmail.com
MAIL_FROM_NAME=Sistema de Citas

LOG_CHANNEL=stack
LOG_LEVEL=info

SESSION_DRIVER=cookie
SESSION_LIFETIME=120

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

**Nota:** Railway automáticamente configurará las variables de MySQL (`DATABASE_URL`, etc.)

### 5️⃣ Generar Dominio Público

1. En Railway → Settings → Networking
2. Click en "Generate Domain"
3. Copia el dominio generado (ej: `tu-app.up.railway.app`)
4. Actualiza `APP_URL` con ese dominio

### 6️⃣ Desplegar

```bash
# Asegúrate de que todo esté commiteado
git add .
git commit -m "Configurar para Railway con correos Gmail"
git push origin main
```

Railway desplegará automáticamente al detectar el push.

---

## ✅ Verificar que Funciona

### 1. Verificar Despliegue

En Railway → Deployments → Ver el status del despliegue

### 2. Ver Logs

Railway → View Logs → Buscar errores

### 3. Probar API

```bash
# Reemplaza con tu dominio de Railway
curl https://tu-app.up.railway.app/api/health
```

### 4. Probar Correos

1. Configura tu frontend para usar la URL de Railway
2. Crea una reserva desde el frontend
3. Verifica que llegue el correo a `julian2002narvaez@gmail.com`

---

## 🔍 Comandos Útiles de Railway CLI

```bash
# Instalar Railway CLI (opcional)
npm install -g @railway/cli

# Login
railway login

# Ver variables
railway variables

# Ver logs en tiempo real
railway logs

# Ejecutar comando en el servidor
railway run php artisan tinker
```

---

## ⚠️ Problemas Comunes

### "No application encryption key"

**Solución:**
```bash
php artisan key:generate --show
# Copia la key y agrégala como APP_KEY en Railway
```

### "Could not find driver" (MySQL)

**Solución:** Asegúrate de que Railway instaló las dependencias de PHP con MySQL:
- En Railway → Variables, verifica que `DATABASE_URL` esté configurado
- Railway debería instalar automáticamente `php-mysql`

### Los correos no llegan

**Verificar:**
1. Variables de correo correctamente configuradas
2. Logs de Railway por errores SMTP
3. Bandeja de spam de Gmail

---

## 📊 Estructura Final en Railway

```
Tu Proyecto Railway/
├── Backend Service (Laravel)
│   ├── Variables de entorno configuradas
│   ├── Dominio público generado
│   └── Logs accesibles
│
└── MySQL Database
    ├── Credenciales auto-generadas
    └── Conectado al backend
```

---

## 🎯 Checklist Final

- [ ] Proyecto creado en Railway
- [ ] MySQL agregado
- [ ] Variables de entorno configuradas (especialmente MAIL_*)
- [ ] Dominio público generado
- [ ] APP_URL actualizado con el dominio
- [ ] Push a GitHub realizado
- [ ] Despliegue exitoso (sin errores en logs)
- [ ] Migraciones ejecutadas
- [ ] API responde correctamente
- [ ] Correos se envían correctamente

---

## 🚀 Listo!

Una vez completados todos los pasos:

1. Tu backend estará en: `https://tu-app.up.railway.app`
2. Los correos se enviarán desde Gmail automáticamente
3. La base de datos estará funcionando
4. Todo listo para conectar tu frontend

**Siguiente paso:** Configurar el frontend para que use la URL de Railway en producción.

---

## 📞 Recursos

- **Railway Docs:** https://docs.railway.app/
- **Railway Discord:** https://discord.gg/railway
- **Laravel Deployment:** https://laravel.com/docs/deployment

**¡Éxito con tu despliegue! 🎉**
