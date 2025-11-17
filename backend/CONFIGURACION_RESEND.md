# 📧 Configuración de Resend para Laravel

## ✅ Estado Actual
Resend está configurado y listo para enviar correos electrónicos.

## 🔧 Configuración Implementada

### 1. Variables de Entorno (.env)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_USERNAME=resend
MAIL_PASSWORD=tu-resend-api-key-aqui
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="Sistema de Citas"
```

### 2. Credenciales de Resend
- **API Key**: Se obtiene desde tu dashboard de Resend
- **Email From**: `onboarding@resend.dev` (correo verificado por Resend)
- **Usuario SMTP**: `resend` (siempre usar este valor)
- **Host SMTP**: `smtp.resend.com`
- **Puerto**: `465` con TLS

## 📝 Pasos de Configuración

### Paso 1: Obtener API Key
1. Ve a [https://resend.com/](https://resend.com/)
2. Inicia sesión en tu cuenta
3. Ve a la sección "API Keys"
4. Copia tu API Key (comienza con `re_`)

### Paso 2: Configurar Variables
1. Abre el archivo `.env`
2. Reemplaza `*****` en `MAIL_PASSWORD` con tu API Key de Resend
3. Guarda el archivo

### Paso 3: Limpiar Caché
```bash
php artisan config:clear
php artisan cache:clear
```

## 🧪 Probar el Envío de Correos

### Opción 1: Script Automatizado
```bash
probar-resend.bat
```

### Opción 2: Tinker Manual
```bash
php artisan tinker
```

Luego ejecuta:
```php
use Illuminate\Support\Facades\Mail;

Mail::raw('Este es un correo de prueba desde Resend', function($message) {
    $message->to('tu-email@ejemplo.com')
            ->subject('Prueba Resend - Sistema de Citas');
});
```

### Opción 3: Endpoint de Prueba
Crea una ruta temporal en `routes/api.php`:
```php
Route::get('/test-email', function () {
    try {
        Mail::raw('Este es un correo de prueba desde Resend', function($message) {
            $message->to('tu-email@ejemplo.com')
                    ->subject('Prueba Resend - Sistema de Citas');
        });
        return response()->json(['message' => 'Correo enviado exitosamente']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
```

Visita: `http://localhost/api/test-email`

## 📧 Usar en tus Mailables

Tu Mailable existente (`BookingConfirmationMail`) funcionará automáticamente con Resend:

```php
use App\Mail\BookingConfirmationMail;
use Illuminate\Support\Facades\Mail;

Mail::to($user->email)->send(new BookingConfirmationMail($booking));
```

## 🚀 Para Producción

### 1. Verificar Dominio (Opcional pero Recomendado)
Para enviar desde tu propio dominio (ej: `noreply@tudominio.com`):

1. Ve a Resend Dashboard → Domains
2. Añade tu dominio
3. Configura los registros DNS (SPF, DKIM, DMARC)
4. Una vez verificado, actualiza `MAIL_FROM_ADDRESS` en `.env`

### 2. Variables de Entorno en Railway/Vercel
Cuando despliegues, configura estas variables:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=465
MAIL_USERNAME=resend
MAIL_PASSWORD=tu-resend-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME=Sistema de Citas
```

## 🔍 Monitoreo y Logs

### Ver Logs en Resend
1. Ve a tu dashboard de Resend
2. Sección "Emails" → verás todos los correos enviados
3. Estado: delivered, bounced, opened, clicked

### Ver Logs en Laravel
```bash
# Ver últimas líneas del log
tail -f storage/logs/laravel.log
```

En Windows:
```bash
type storage\logs\laravel.log
```

## ⚠️ Solución de Problemas

### Error: "Failed to authenticate"
- Verifica que la API Key sea correcta
- Asegúrate de no tener espacios antes/después de la API Key
- La API Key debe comenzar con `re_`

### Error: "Connection timeout"
- Verifica tu firewall
- Prueba cambiar el puerto a `587` y `MAIL_ENCRYPTION=tls`

### Correos no llegan
1. Revisa la carpeta de spam
2. Verifica en el dashboard de Resend si el correo fue enviado
3. Revisa los logs de Laravel: `storage/logs/laravel.log`

### Para depurar
Activa el modo debug en `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## 📊 Límites de Resend

### Plan Gratuito
- 100 correos/día
- 3,000 correos/mes
- Perfecto para desarrollo y testing

### Para más correos
Considera actualizar a un plan de pago en [resend.com/pricing](https://resend.com/pricing)

## 🎯 Ventajas de Resend

✅ **Fácil de configurar** - Solo necesitas una API Key  
✅ **Alta entregabilidad** - Buena reputación de IPs  
✅ **Dashboard intuitivo** - Monitorea todos tus correos  
✅ **API moderna** - Compatible con SMTP y API REST  
✅ **Plan gratuito generoso** - 100 correos/día  
✅ **Sin verificación 2FA complicada** - A diferencia de Gmail  

## 🔗 Recursos

- [Documentación de Resend](https://resend.com/docs)
- [Configuración SMTP de Resend](https://resend.com/docs/send-with-smtp)
- [Verificar dominio](https://resend.com/docs/dashboard/domains/introduction)

---

**¿Necesitas ayuda?** Revisa los logs o contacta al equipo de soporte de Resend.
