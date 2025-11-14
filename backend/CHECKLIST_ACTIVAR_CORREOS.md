# 🎯 CHECKLIST COMPLETO: Activar Correos en Railway

## 📋 PASO 1: Subir Código Actualizado

```bash
cd c:\laragon\www\back-ProyectoSas\backend
git add .
git commit -m "fix: sistema de colas para correos + configuración Sanctum"
git push origin main
```

**¿Qué incluye este commit?**
- ✅ Migraciones para tablas `jobs` y `job_batches`
- ✅ `BookingConfirmationMail` con `ShouldQueue`
- ✅ `BookingController` usando `Mail::queue()`
- ✅ `railway.json` con worker configurado
- ✅ Comando `email:test` para pruebas

---

## 📋 PASO 2: Configurar Variables en Railway

### 2.1 Ir al Dashboard de Railway
1. https://railway.app
2. Selecciona tu proyecto
3. Click en servicio **backend**
4. Click en pestaña **"Variables"**

### 2.2 Cambiar Estas Variables

| Variable | ❌ Valor Actual | ✅ Valor Correcto |
|----------|----------------|-------------------|
| `SANCTUM_STATEFUL_DOMAINS` | `"https://saas-citas.vercel.app/"` | `saas-citas.vercel.app` |
| `SESSION_DOMAIN` | `.railway.app` | `.vercel.app` |

### 2.3 Verificar Estas Variables

```env
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=julian2002narvaez@gmail.com
MAIL_PASSWORD=htzeopywmpepctlb
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=julian2002narvaez@gmail.com
```

### 2.4 Guardar Cambios
Railway hará **redeploy automático** (espera 2-3 minutos)

---

## 📋 PASO 3: Verificar Despliegue

### 3.1 Ver Logs de Despliegue
1. En Railway, click en **"Deployments"**
2. Click en el último deployment
3. Click en **"View Logs"**

### 3.2 Buscar en los Logs:
```
✅ "Running migrations..."
✅ "Migrated: create_jobs_table"
✅ "Migrated: create_job_batches_table"
✅ "Starting queue worker..."
```

### 3.3 Si NO ves el worker corriendo:
```bash
# Desde terminal local con Railway CLI
railway run php artisan migrate --force
```

---

## 📋 PASO 4: Probar Envío de Correos

### 4.1 Desde Railway CLI (Opcional)
```bash
railway run php artisan email:test tu@email.com
```

### 4.2 Desde el Frontend (Principal)
1. Ve a: https://saas-citas.vercel.app
2. Selecciona un negocio
3. Crea una reserva con TU email
4. Completa el formulario
5. Click en "Confirmar Reserva"

### 4.3 Verificar en los Logs
En Railway → Deployments → View Logs, busca:
```
[INFO] Encolando correo de confirmación
[INFO] Correo encolado exitosamente
[INFO] Processing: App\Mail\BookingConfirmationMail
[INFO] Processed: App\Mail\BookingConfirmationMail
```

### 4.4 Verificar Email
- Revisa tu bandeja de entrada
- Revisa carpeta de **SPAM** (importante)
- El correo viene de: `julian2002narvaez@gmail.com`

---

## 📋 PASO 5: Verificar en Base de Datos (Opcional)

Si tienes acceso a MySQL de Railway:

```sql
-- Ver tabla de trabajos
SELECT * FROM jobs LIMIT 10;

-- Ver trabajos fallidos
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;

-- Contar trabajos procesados hoy
SELECT COUNT(*) FROM jobs WHERE created_at >= CURDATE();
```

---

## ❌ TROUBLESHOOTING

### Problema 1: "Table 'jobs' doesn't exist"

**Solución:**
```bash
railway run php artisan migrate --force
```

### Problema 2: No llega el correo

**Verificar:**
1. ¿Hay trabajos en cola?
   ```bash
   railway run php artisan tinker --execute="echo DB::table('jobs')->count();"
   ```

2. ¿Hay trabajos fallidos?
   ```bash
   railway run php artisan queue:failed
   ```

3. ¿El worker está corriendo?
   - Ver logs de Railway
   - Buscar: "Processing: App\Mail\BookingConfirmationMail"

4. ¿Revisa SPAM?

### Problema 3: Error de autenticación SMTP

**Posibles causas:**
- Contraseña de aplicación incorrecta
- Gmail bloqueó el acceso
- Verificación en 2 pasos desactivada

**Solución:**
1. Ir a: https://myaccount.google.com/apppasswords
2. Generar nueva contraseña
3. Actualizar `MAIL_PASSWORD` en Railway

### Problema 4: CORS / Sanctum

Si las peticiones desde Vercel fallan:
```env
# Verificar estas variables
SANCTUM_STATEFUL_DOMAINS=saas-citas.vercel.app
SESSION_DOMAIN=.vercel.app
```

---

## ✅ CHECKLIST FINAL

Marca cada ítem cuando lo completes:

- [ ] 1. Código subido a Git
- [ ] 2. Variables corregidas en Railway
  - [ ] SANCTUM_STATEFUL_DOMAINS sin https:// ni /
  - [ ] SESSION_DOMAIN=.vercel.app
  - [ ] QUEUE_CONNECTION=database
- [ ] 3. Deployment completado sin errores
- [ ] 4. Migraciones ejecutadas (logs confirman)
- [ ] 5. Worker corriendo (logs confirman)
- [ ] 6. Reserva de prueba creada
- [ ] 7. Logs muestran "Correo encolado exitosamente"
- [ ] 8. Logs muestran "Processing: BookingConfirmationMail"
- [ ] 9. Correo recibido en bandeja o spam
- [ ] 10. Frontend funciona sin errores CORS

---

## 📊 MONITOREO CONTINUO

### Ver logs en tiempo real:
```bash
railway logs --follow
```

### Ver estado de colas (local):
```bash
cd c:\laragon\www\back-ProyectoSas\backend
verificar-colas.bat
```

### Reintentar trabajos fallidos:
```bash
railway run php artisan queue:retry all
```

---

## 🎉 ¡LISTO!

Si completaste todos los pasos del checklist, tus correos deberían estar funcionando perfectamente.

**Tiempo estimado:** 10-15 minutos

**Costo adicional:** $1-2/mes (worker de colas)

---

## 📞 SOPORTE

Si después de seguir todos los pasos aún no funciona:

1. **Captura logs completos:**
   ```bash
   railway logs > railway-logs.txt
   ```

2. **Verifica variables:**
   ```bash
   railway variables > railway-vars.txt
   ```

3. **Ejecuta diagnóstico:**
   ```bash
   railway run php artisan queue:failed
   railway run php artisan tinker --execute="echo 'Jobs: '.DB::table('jobs')->count();"
   ```

4. **Revisa documentación:**
   - `RESUMEN_PROBLEMA_CORREOS.md` - Explicación del problema
   - `RAILWAY_COLAS_GUIA_COMPLETA.md` - Guía detallada
   - `CORRECCION_SANCTUM.md` - Configuración Sanctum

---

**Creado:** 14 de noviembre de 2025  
**Versión:** 1.0
