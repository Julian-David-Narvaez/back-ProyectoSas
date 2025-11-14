# 🚂 Despliegue en Railway - Configuración de Correos

## 📧 Variables de Entorno para Railway

Para que los correos funcionen en Railway con Gmail, necesitas configurar estas variables de entorno en tu proyecto de Railway:

### 1. Variables de Correo (Gmail)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=julian2002narvaez@gmail.com
MAIL_PASSWORD=xxgxjcupzmnxadxj
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=julian2002narvaez@gmail.com
MAIL_FROM_NAME=Sistema de Citas
```

### 2. Otras Variables Importantes

```env
APP_NAME=Sistema de Citas
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:IELATxnf9G3fGLgqR4uhva8YnLtnoUVGavZOJwZw2vY=
APP_URL=https://tu-app.railway.app

DB_CONNECTION=mysql
DB_HOST=tu-host-railway
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=tu-password-railway

APP_TIMEZONE=America/Bogota
```

---

## 🔧 Pasos para Configurar en Railway

### 1. Crear Proyecto en Railway

1. Ve a https://railway.app/
2. Conecta tu repositorio de GitHub
3. Selecciona el proyecto `back-ProyectoSas`

### 2. Configurar Base de Datos MySQL

1. En Railway, haz clic en "New" → "Database" → "Add MySQL"
2. Railway generará automáticamente las credenciales
3. Copia las credenciales de MySQL que te proporciona

### 3. Configurar Variables de Entorno

En Railway → Settings → Variables:

**Variables Básicas:**
- `APP_ENV` = `production`
- `APP_DEBUG` = `false`
- `APP_KEY` = `base64:IELATxnf9G3fGLgqR4uhva8YnLtnoUVGavZOJwZw2vY=`
- `APP_URL` = `https://tu-dominio.railway.app`

**Variables de Base de Datos:**
Railway automáticamente configura:
- `DATABASE_URL` (Railway lo genera)

O manualmente:
- `DB_CONNECTION` = `mysql`
- `DB_HOST` = (proporcionado por Railway)
- `DB_PORT` = `3306`
- `DB_DATABASE` = `railway`
- `DB_USERNAME` = `root`
- `DB_PASSWORD` = (proporcionado por Railway)

**Variables de Correo (Gmail):**
- `MAIL_MAILER` = `smtp`
- `MAIL_HOST` = `smtp.gmail.com`
- `MAIL_PORT` = `587`
- `MAIL_USERNAME` = `julian2002narvaez@gmail.com`
- `MAIL_PASSWORD` = `xxgxjcupzmnxadxj`
- `MAIL_ENCRYPTION` = `tls`
- `MAIL_FROM_ADDRESS` = `julian2002narvaez@gmail.com`
- `MAIL_FROM_NAME` = `Sistema de Citas`

**Otras Variables:**
- `APP_TIMEZONE` = `America/Bogota`

### 4. Configurar el archivo railway.json

Railway ya detectará automáticamente el archivo `railway.json` en tu proyecto.

---

## 📝 Archivo railway.json Actualizado

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "NIXPACKS",
    "buildCommand": "composer install --optimize-autoloader --no-dev && php artisan config:cache && php artisan route:cache && php artisan view:cache"
  },
  "deploy": {
    "startCommand": "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

---

## 🚀 Proceso de Despliegue

### Automático (Recomendado)

1. **Push a GitHub:**
   ```bash
   git add .
   git commit -m "Configurar correos para producción"
   git push origin main
   ```

2. **Railway desplegará automáticamente** cuando detecte cambios en la rama main

### Manual (desde Railway)

1. Ve a tu proyecto en Railway
2. Click en "Deploy" → "Trigger Deploy"

---

## ✅ Verificar el Despliegue

### 1. Verificar que la aplicación esté corriendo

```bash
curl https://tu-app.railway.app/api/health
```

### 2. Ver logs en Railway

En Railway → Deployments → Ver logs en tiempo real

### 3. Probar envío de correos

Crea una reserva desde el frontend en producción y verifica que llegue el correo a tu Gmail.

---

## 🔒 Seguridad - Variables Sensibles

### ⚠️ IMPORTANTE: No commitear credenciales

Asegúrate de que `.env` esté en `.gitignore`:

```bash
# Verificar que .env está ignorado
cat .gitignore | grep .env
```

### Variables que NUNCA deben estar en el código:

- ❌ `MAIL_PASSWORD`
- ❌ `DB_PASSWORD`
- ❌ `APP_KEY`
- ❌ Cualquier credencial o secret

✅ **Siempre configúralas como variables de entorno en Railway**

---

## 🐛 Troubleshooting

### Error: "Connection refused" al enviar correo

**Solución:** Verifica que las variables de correo estén correctamente configuradas en Railway.

```bash
# Ver variables de entorno en Railway CLI
railway variables
```

### Error: "Authentication failed"

**Posibles causas:**
1. La contraseña de aplicación expiró o fue revocada
2. Gmail bloqueó el acceso por seguridad
3. Contraseña incorrecta

**Solución:**
1. Genera una nueva contraseña de aplicación
2. Actualiza `MAIL_PASSWORD` en Railway
3. Redeploy

### Los correos no llegan

1. **Verifica logs en Railway:**
   ```
   Railway → Deployments → Logs
   ```

2. **Busca errores de correo:**
   ```
   Buscar: "Error enviando correo" o "SMTP"
   ```

3. **Verifica Gmail:**
   - Revisa la carpeta de "Enviados" en Gmail
   - Verifica que no haya límites de envío alcanzados
   - Gmail permite ~500 correos/día con cuentas gratuitas

### Error: "No application encryption key has been specified"

**Solución:**
```bash
# Generar nueva key
php artisan key:generate --show

# Copiar la key y agregarla en Railway como APP_KEY
```

---

## 📊 Límites de Gmail para Producción

- **Límite de envío:** ~500 correos/día
- **Límite por hora:** ~100 correos/hora

### Para mayor volumen, considera:

1. **SendGrid** (100 correos/día gratis, luego de pago)
2. **Mailgun** (5,000 correos/mes gratis)
3. **Amazon SES** (62,000 correos/mes gratis en AWS)
4. **Postmark** (100 correos/mes gratis)

---

## 🔄 Cambiar de Mailtrap (dev) a Gmail (producción)

### En Local (Desarrollo):
```env
# .env local
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=37028400b8a68b
MAIL_PASSWORD=02afefb44d33d1
```

### En Railway (Producción):
```env
# Variables de entorno en Railway
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=julian2002narvaez@gmail.com
MAIL_PASSWORD=xxgxjcupzmnxadxj
```

---

## 📱 Próximos Pasos Sugeridos

1. ✅ Configurar dominio personalizado en Railway
2. ✅ Configurar HTTPS (Railway lo hace automáticamente)
3. ✅ Configurar CORS para tu frontend
4. ✅ Monitorear logs de producción
5. ✅ Configurar backups de base de datos
6. ✅ Implementar rate limiting
7. ✅ Agregar monitoring (Sentry, Bugsnag)

---

## 🎯 Checklist de Despliegue

- [ ] Proyecto creado en Railway
- [ ] Base de datos MySQL agregada
- [ ] Variables de entorno configuradas
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` configurado
- [ ] Variables de correo Gmail configuradas
- [ ] `railway.json` actualizado
- [ ] Push a GitHub
- [ ] Despliegue exitoso en Railway
- [ ] Migraciones ejecutadas
- [ ] Prueba de envío de correo exitosa
- [ ] Frontend configurado con URL de producción

---

## 📞 Soporte

Si encuentras problemas:

1. **Logs de Railway:** Deployments → View Logs
2. **Documentación de Railway:** https://docs.railway.app/
3. **Laravel Logs:** Storage/logs/laravel.log (accesible vía SSH en Railway)

**¡Éxito con tu despliegue! 🚀**
