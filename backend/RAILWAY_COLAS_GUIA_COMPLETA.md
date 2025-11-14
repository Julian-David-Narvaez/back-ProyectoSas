# 🚂 Guía Definitiva: Railway + Colas + Correos

## 🎯 CONFIGURACIÓN RÁPIDA (5 minutos)

### Paso 1: Subir Código
```bash
cd c:\laragon\www\back-ProyectoSas\backend
git add .
git commit -m "feat: sistema de colas para correos electrónicos"
git push origin main
```

### Paso 2: Ejecutar Migraciones en Railway

**Opción A - Desde Railway CLI:**
```bash
railway run php artisan migrate --force
```

**Opción B - Desde el Dashboard:**
1. Ve a tu proyecto en Railway
2. Click en tu servicio backend
3. Click en "Deployments" → último deployment
4. Click en "View Logs"
5. Deberías ver: `Running migrations...`

### Paso 3: Configurar Worker (ELEGIR UNA OPCIÓN)

#### 🟢 OPCIÓN 1: Worker en Mismo Contenedor (Más Simple)

**Ya está configurado en `railway.json`** ✅

El comando actual ejecuta:
```bash
php artisan migrate --force && php artisan queue:work --tries=3 --timeout=90 --daemon & php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
```

**Ventajas:**
- ✅ Sin configuración adicional
- ✅ Un solo servicio
- ✅ Más económico

**Desventajas:**
- ❌ Si el worker falla, puede afectar el API
- ❌ Menos escalable

---

#### 🔵 OPCIÓN 2: Worker Separado (Recomendado para Producción)

1. **En Railway Dashboard:**
   - Click en "New" → "Empty Service"
   - Nombre: `backend-queue-worker`

2. **Conectar Repositorio:**
   - Source: Same as backend
   - Root Directory: `backend`

3. **Configurar Variables:**
   Copia TODAS las variables del servicio backend:
   ```env
   APP_KEY=base64:o1Vh3O1hVdp+Wr46RKiI2Wt33daebbSwWqv/TkGpgcA=
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://back-proyectosas-pagina.up.railway.app
   
   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.RAILWAY_PRIVATE_DOMAIN}}
   DB_PORT=3306
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=julian2002narvaez@gmail.com
   MAIL_PASSWORD=htzeopywmpepctlb
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=julian2002narvaez@gmail.com
   MAIL_FROM_NAME="Sistema de Citas"
   
   QUEUE_CONNECTION=database
   ```

4. **Start Command:**
   ```bash
   php artisan queue:work --verbose --tries=3 --timeout=90 --max-time=3600
   ```

5. **Deploy**

**Ventajas:**
- ✅ Aislamiento total
- ✅ Se reinicia automáticamente
- ✅ No afecta rendimiento del API
- ✅ Escalable independientemente

**Desventajas:**
- ❌ Costo adicional ($2-3/mes)
- ❌ Configuración extra

---

## ✅ VERIFICACIÓN

### 1. Verificar Migraciones
Desde Railway CLI o SSH:
```bash
railway run php artisan migrate:status
```

Deberías ver:
```
✅ create_jobs_table ...................... ran
✅ create_job_batches_table ............... ran
```

### 2. Probar Envío de Correo
```bash
railway run php artisan email:test tu@email.com
```

### 3. Crear Reserva desde Frontend
1. Ve a tu frontend: https://saas-citas.vercel.app
2. Crea una reserva
3. Revisa logs en Railway:
   ```
   [INFO] Encolando correo de confirmación
   [INFO] Correo encolado exitosamente
   [INFO] Processing: App\Mail\BookingConfirmationMail
   [INFO] Processed: App\Mail\BookingConfirmationMail
   ```

### 4. Verificar en Base de Datos
```sql
-- Ver trabajos pendientes
SELECT * FROM jobs;

-- Ver trabajos fallidos
SELECT * FROM failed_jobs;
```

---

## 🐛 TROUBLESHOOTING

### Problema: "Table 'jobs' doesn't exist"
**Solución:**
```bash
railway run php artisan migrate --force
```

### Problema: Los correos no se envían
**Verificar:**
1. ¿El worker está corriendo?
   ```bash
   railway logs --service=backend
   ```
   Busca: `Processing: App\Mail\BookingConfirmationMail`

2. ¿Hay trabajos en cola?
   ```bash
   railway run php artisan tinker --execute="echo DB::table('jobs')->count();"
   ```

3. ¿Hay trabajos fallidos?
   ```bash
   railway run php artisan queue:failed
   ```

### Problema: "Connection refused [smtp.gmail.com:587]"
**Solución:** Verificar contraseña de aplicación de Gmail

1. Ir a: https://myaccount.google.com/apppasswords
2. Generar nueva contraseña
3. Actualizar en Railway: `MAIL_PASSWORD=nueva_contraseña`

### Problema: Worker se detiene constantemente
**Si usas Opción 1 (mismo contenedor):**
```bash
# Cambiar a modo daemon más estable
php artisan queue:work --daemon --sleep=3 --tries=3 &
```

**Si usas Opción 2 (worker separado):**
- Railway reiniciará automáticamente el worker
- Revisar logs para ver la causa

---

## 📊 MONITOREO

### Ver Logs en Tiempo Real
```bash
railway logs --follow
```

### Ver Estado de Colas (Local)
```bash
cd c:\laragon\www\back-ProyectoSas\backend
verificar-colas.bat
```

### Reintentar Trabajos Fallidos
```bash
railway run php artisan queue:retry all
```

### Limpiar Trabajos Fallidos
```bash
railway run php artisan queue:flush
```

---

## 💰 COSTOS ESTIMADOS

| Configuración | RAM | CPU | Costo/Mes |
|---------------|-----|-----|-----------|
| Opción 1 (Mismo contenedor) | 512 MB | 0.5 vCPU | ~$5 |
| Opción 2 (Worker separado) | Backend: 512 MB<br>Worker: 256 MB | Backend: 0.5 vCPU<br>Worker: 0.25 vCPU | ~$7-8 |

**Railway Tier Gratuito:** $5 de crédito/mes

---

## 🎉 CHECKLIST FINAL

- [ ] Código subido a Git
- [ ] Migraciones ejecutadas en Railway
- [ ] Worker configurado (Opción 1 o 2)
- [ ] Variables de entorno verificadas
- [ ] Correo de prueba enviado exitosamente
- [ ] Reserva de prueba creada
- [ ] Correo de confirmación recibido
- [ ] Logs revisados sin errores

---

## 📞 SOPORTE

**Si aún no funciona:**

1. Captura de pantalla de logs de Railway
2. Resultado de: `railway run php artisan queue:failed`
3. Verificar variables: `railway variables`

**Comandos útiles:**
```bash
# Ver todas las variables
railway variables

# Ejecutar comando en Railway
railway run [comando]

# Abrir shell en Railway
railway shell

# Ver servicios
railway status
```

---

**¡Listo! 🚀** Tus correos ahora se enviarán de forma asíncrona y eficiente.
