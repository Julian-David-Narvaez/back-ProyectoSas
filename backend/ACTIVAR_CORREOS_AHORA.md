# 🚀 Guía Rápida: Activar Envío de Correos AHORA

## ⚡ Opción 1: Envío Real con Gmail (5 minutos)

### Paso 1: Obtener Contraseña de Aplicación de Google

1. Ve a: https://myaccount.google.com/security
2. Busca "Verificación en dos pasos" → Actívala si no lo está
3. Ve a "Contraseñas de aplicaciones" (al final de la página)
4. Selecciona "Correo" y "Windows Computer"
5. Haz clic en "Generar"
6. **Copia la contraseña de 16 caracteres** (sin espacios)

### Paso 2: Actualizar tu archivo `.env`

Abre: `c:\laragon\www\back-ProyectoSas\backend\.env`

Busca estas líneas:
```env
MAIL_MAILER=log
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=julian2002narvaez@gmail.com
MAIL_PASSWORD=
```

Cámbialo a:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=xxxx-xxxx-xxxx-xxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@gmail.com
MAIL_FROM_NAME="Sistema de Citas"
```

⚠️ **IMPORTANTE**: 
- Cambia `tu-email@gmail.com` por tu email real
- Pega la contraseña de aplicación (16 caracteres) sin espacios

### Paso 3: Limpiar Cache
```bash
cd c:\laragon\www\back-ProyectoSas\backend
php artisan config:clear
php artisan cache:clear
```

### Paso 4: ¡Probar!
```bash
php artisan email:test-booking --email=tu-email@gmail.com
```

**✅ Deberías recibir el correo en 10-30 segundos**

---

## ⚡ Opción 2: Testing con Mailtrap (2 minutos)

### Paso 1: Crear Cuenta Mailtrap

1. Ve a: https://mailtrap.io/register/signup
2. Regístrate gratis (no requiere tarjeta)
3. Ve a "Email Testing" → "Inboxes" → "My Inbox"
4. Copia las credenciales que aparecen

### Paso 2: Actualizar `.env`

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu-username-de-mailtrap
MAIL_PASSWORD=tu-password-de-mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@citas.com
MAIL_FROM_NAME="Sistema de Citas"
```

### Paso 3: Limpiar Cache
```bash
php artisan config:clear
php artisan cache:clear
```

### Paso 4: Probar
```bash
php artisan email:test-booking
```

### Paso 5: Ver el Correo
Ve a tu inbox de Mailtrap y verás el correo capturado ahí.

---

## 🧪 Cómo Probar el Sistema Completo

### Método 1: Desde el Frontend (Prueba Real)

1. **Inicia el backend**:
   ```bash
   cd c:\laragon\www\back-ProyectoSas\backend
   php artisan serve
   ```

2. **Inicia el frontend**:
   ```bash
   cd c:\laragon\www\Front-ProyectoSas\frontend
   npm run dev
   ```

3. **Agenda una cita**:
   - Ve a http://localhost:5173
   - Selecciona un negocio
   - Elige un servicio
   - Selecciona fecha y hora
   - **Importante**: Usa un email real (el tuyo) para recibir la confirmación
   - Completa la reserva

4. **Revisa tu email** (o Mailtrap si usaste esa opción)

### Método 2: Comando Rápido (Para Testing)

```bash
# Asegúrate de tener al menos 1 reserva en la BD
cd c:\laragon\www\back-ProyectoSas\backend

# Probar con tu email
php artisan email:test-booking --email=tu-email@gmail.com

# Ver resultado
```

### Método 3: Laravel Tinker (Avanzado)

```bash
php artisan tinker
```

Luego ejecuta:
```php
// Crear reserva de prueba
$user = App\Models\User::first();
$business = App\Models\Business::first();
$service = App\Models\Service::first();

$booking = App\Models\Booking::create([
    'business_id' => $business->id,
    'service_id' => $service->id,
    'customer_name' => 'Cliente Prueba',
    'customer_email' => 'tu-email@gmail.com', // TU EMAIL AQUÍ
    'start_at' => now()->addDay()->setTime(10, 0),
    'end_at' => now()->addDay()->setTime(11, 0),
    'status' => 'confirmed',
]);

// Enviar correo
Mail::to('tu-email@gmail.com')->send(new App\Mail\BookingConfirmationMail($booking));

// Salir
exit
```

---

## 📊 Verificar que Funciona

### Si usas modo `log` (actual):
```bash
# Ver últimos logs
Get-Content c:\laragon\www\back-ProyectoSas\backend\storage\logs\laravel.log -Tail 100

# Buscar correos enviados
findstr /C:"Intentando enviar correo" c:\laragon\www\back-ProyectoSas\backend\storage\logs\laravel.log
findstr /C:"Correo enviado exitosamente" c:\laragon\www\back-ProyectoSas\backend\storage\logs\laravel.log
```

### Si usas SMTP (Gmail/Mailtrap):
1. **Gmail**: Revisa tu bandeja de entrada y carpeta spam
2. **Mailtrap**: Ve a tu inbox en https://mailtrap.io

---

## ❌ Si Algo Sale Mal

### Error: "Connection refused" o "Connection timeout"

**Solución 1**: Verificar configuración
```bash
php artisan config:clear
php artisan tinker
>>> config('mail.mailers.smtp')
>>> exit
```

**Solución 2**: Verificar que tu firewall permite SMTP
- Gmail: Puerto 587
- Mailtrap: Puerto 2525

**Solución 3**: Volver a modo log temporalmente
```env
MAIL_MAILER=log
```
```bash
php artisan config:clear
```

### Error: "Authentication failed"

**Gmail**:
- ✅ Usa contraseña de aplicación, NO tu contraseña normal
- ✅ Verifica que la verificación en 2 pasos esté activa
- ✅ Genera una nueva contraseña de aplicación

**Mailtrap**:
- ✅ Verifica username y password de Mailtrap
- ✅ Usa el inbox correcto

### El correo no llega (Gmail)

1. **Revisa spam** en tu Gmail
2. **Espera 1-2 minutos** (a veces hay delay)
3. **Verifica logs**:
   ```bash
   Get-Content storage\logs\laravel.log -Tail 50
   ```
4. **Prueba enviar a otro email**:
   ```bash
   php artisan email:test-booking --email=otro-email@gmail.com
   ```

---

## ✅ Checklist de Activación

- [ ] Obtener contraseña de aplicación de Google
- [ ] Actualizar `MAIL_MAILER` de `log` a `smtp`
- [ ] Configurar `MAIL_USERNAME` con tu email
- [ ] Configurar `MAIL_PASSWORD` con contraseña de aplicación
- [ ] Configurar `MAIL_FROM_ADDRESS` con tu email
- [ ] Ejecutar `php artisan config:clear`
- [ ] Ejecutar `php artisan cache:clear`
- [ ] Probar con `php artisan email:test-booking --email=tu-email@gmail.com`
- [ ] Verificar recepción del correo
- [ ] Agendar cita de prueba desde el frontend

---

## 📞 Comandos Útiles

```bash
# Limpiar toda la cache
php artisan optimize:clear

# Ver configuración de mail actual
php artisan tinker
>>> config('mail')
>>> exit

# Probar envío de correo
php artisan email:test-booking --email=tu-email@gmail.com

# Ver logs en tiempo real
Get-Content storage\logs\laravel.log -Wait -Tail 50

# Ver últimos errores
findstr /C:"ERROR" storage\logs\laravel.log
```

---

## 🎯 Próximos Pasos

Una vez que funcione el envío de correos:

1. **Personalizar el template**: Edita `resources/views/emails/booking/confirmation.blade.php`
2. **Agregar logo**: Agrega el logo de cada negocio en el correo
3. **Correos adicionales**:
   - Recordatorio 24h antes
   - Notificación de cancelación
   - Aviso al dueño del negocio
4. **WhatsApp**: Integrar notificaciones por WhatsApp

---

## 📚 Documentación Completa

- `CORREOS_LISTOS.md` - Resumen completo del sistema
- `CONFIGURACION_CORREOS.md` - Guía detallada de configuración
- Este archivo - Guía rápida de activación

**¡Éxito! 🚀**
