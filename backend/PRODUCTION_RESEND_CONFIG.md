# 🚀 Configuración de Resend para Producción en Railway

## ✅ Variables que DEBES AGREGAR en Railway

Ve a tu proyecto en Railway → **Variables** y agrega esta variable que falta:

```env
RESEND_API_KEY=re_CHhtom9R_NQLESLTeNNd77WdAXGgqjPZf
```

## 📋 Variables de Entorno Completas para Railway

```env
# Aplicación
APP_DEBUG=false
APP_ENV=production
APP_KEY=base64:o1Vh3O1hVdp+Wr46RKiI2Wt33daebbSwWqv/TkGpgcA=
APP_NAME=SaaS_Citas
APP_TIMEZONE=America/Bogota
APP_URL=https://back-proyectosas-pagina.up.railway.app

# Base de Datos
DB_CONNECTION=mysql
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_HOST=${{MySQL.RAILWAY_PRIVATE_DOMAIN}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_USERNAME=${{MySQL.MYSQLUSER}}

# Logging y Colas
LOG_CHANNEL=stack
LOG_LEVEL=error
QUEUE_CONNECTION=database

# Session y CORS
SESSION_DOMAIN=.railway.app
SESSION_DRIVER=cookie
SANCTUM_STATEFUL_DOMAINS=https://saas-citas.vercel.app/

# Correo - Resend
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="Sistema de Citas"
MAIL_HOST=smtp.resend.com
MAIL_MAILER=smtp
MAIL_PASSWORD=re_CHhtom9R_NQLESLTeNNd77WdAXGgqjPZf
MAIL_PORT=465
MAIL_USERNAME=resend

# ⚠️ IMPORTANTE: AGREGAR ESTA VARIABLE
RESEND_API_KEY=re_CHhtom9R_NQLESLTeNNd77WdAXGgqjPZf
```

## 🔧 Cambios Recomendados

### 1. **Logging más conservador:**
```env
LOG_LEVEL=error  # En lugar de "debug"
```

### 2. **Optimizaciones de rendimiento:**
Agrega estas variables para mejor rendimiento:

```env
# Cache y optimizaciones
CACHE_DRIVER=file
FILESYSTEM_DISK=local
BROADCAST_DRIVER=log

# Session optimizada
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

## 🚨 Validaciones de Seguridad

### Variables que están BIEN configuradas:
- ✅ `APP_DEBUG=false` 
- ✅ `APP_ENV=production`
- ✅ `QUEUE_CONNECTION=database` 
- ✅ `SESSION_DOMAIN=.railway.app`

### ⚠️ Recomendaciones adicionales:

1. **Verificar dominio en Resend:**
   - Ve a [Resend Dashboard](https://resend.com/domains)
   - Agrega tu dominio personalizado
   - Actualiza `MAIL_FROM_ADDRESS` con tu dominio

2. **Monitoreo de correos:**
   - Dashboard de Resend: https://resend.com/emails
   - Logs de Railway: Dashboard → Deployments → Logs

3. **Backup de API Key:**
   - Guarda `RESEND_API_KEY` en lugar seguro
   - Considera usar diferentes keys para staging/producción

## 🧪 Probar en Producción

### 1. Después del despliegue:
```bash
curl "https://back-proyectosas-pagina.up.railway.app/api/test-basic-resend?email=tu-email@ejemplo.com"
```

### 2. Verificar logs:
- Railway Dashboard → Logs
- Busca: "Email enviado exitosamente" o errores

### 3. Dashboard de Resend:
- Revisa que los correos aparezcan como "delivered"
- Verifica métricas de entrega

## 🔄 Comandos de Actualización

Después de agregar las variables, ejecuta en Railway:

```bash
php artisan config:clear
php artisan cache:clear
php artisan queue:restart  # Si usas workers
```

## 📊 Límites de Resend

### Plan Gratuito:
- **100 correos/día**
- **3,000 correos/mes**

### Para más volumen:
- Actualiza plan en [resend.com/pricing](https://resend.com/pricing)
- O configura otro proveedor (SendGrid, Mailgun)

## 🛡️ Seguridad en Producción

### Variables sensibles:
- ✅ `RESEND_API_KEY` - Solo en Railway, nunca en código
- ✅ `APP_KEY` - Específica para producción  
- ✅ `DB_*` - Variables de Railway

### CORS configurado:
- ✅ `SANCTUM_STATEFUL_DOMAINS` apunta a Vercel
- ✅ Frontend puede hacer requests autenticados

## 🔍 Troubleshooting

### Si los correos no se envían:

1. **Verificar variable:**
   ```bash
   # En Railway logs, buscar:
   echo $RESEND_API_KEY
   ```

2. **Verificar conectividad:**
   ```bash
   # Debe responder desde Railway
   curl -I https://api.resend.com
   ```

3. **Logs específicos:**
   ```bash
   # Buscar en Railway logs:
   "Resend" OR "Email" OR "mail"
   ```

### Errores comunes:

- **"API Key not found"** → Falta `RESEND_API_KEY`
- **"Invalid from address"** → Verifica `MAIL_FROM_ADDRESS`
- **"Rate limit exceeded"** → Superaste límite gratuito

## 📈 Monitoreo Recomendado

### Métricas importantes:
- Correos enviados/día
- Tasa de entrega
- Errores de envío
- Tiempo de respuesta

### Alertas:
- Configurar notificaciones en Resend dashboard
- Monitorear logs de Railway para errores

---

**¡Listo para producción!** 🚀 

Solo agrega `RESEND_API_KEY` en Railway y tus correos funcionarán perfectamente.