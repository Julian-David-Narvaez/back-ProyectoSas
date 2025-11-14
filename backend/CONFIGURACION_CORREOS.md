# 📧 Configuración de Envío de Correos

## Estado Actual
✅ El sistema ya está configurado para enviar correos de confirmación cuando un cliente agenda una cita.

### Funcionalidades implementadas:
- ✅ Envío automático de correo al crear una reserva
- ✅ Plantilla de correo con toda la información de la cita
- ✅ Manejo de errores con logs
- ✅ Información detallada del negocio, servicio, fecha y hora

## 🔧 Configuración para Envío Real de Correos

### Opción 1: Gmail (Recomendado para pruebas)

1. **Generar contraseña de aplicación de Gmail:**
   - Ve a tu cuenta de Google: https://myaccount.google.com/
   - Seguridad → Verificación en dos pasos (actívala si no lo está)
   - Contraseñas de aplicaciones → Genera una nueva contraseña
   - Copia la contraseña generada (16 caracteres)

2. **Actualiza tu archivo `.env`:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-contraseña-de-aplicación-de-16-caracteres
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@gmail.com
MAIL_FROM_NAME="Nombre de tu Negocio"
```

3. **Reinicia tu servidor:**
```bash
php artisan config:clear
php artisan cache:clear
```

### Opción 2: Mailtrap (Para desarrollo/testing)

Mailtrap es ideal para testing ya que captura todos los correos sin enviarlos realmente.

1. **Crea una cuenta gratuita:** https://mailtrap.io/

2. **Obtén las credenciales del inbox de prueba**

3. **Actualiza tu archivo `.env`:**
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu-username-de-mailtrap
MAIL_PASSWORD=tu-password-de-mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@tucitas.com
MAIL_FROM_NAME="Sistema de Citas"
```

### Opción 3: SendGrid (Para producción)

SendGrid es excelente para producción, con plan gratuito de 100 correos/día.

1. **Crea una cuenta:** https://sendgrid.com/

2. **Genera una API Key:**
   - Settings → API Keys → Create API Key
   - Copia tu API Key

3. **Actualiza tu archivo `.env`:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=tu-api-key-de-sendgrid
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@tudominio.com
MAIL_FROM_NAME="Tu Negocio"
```

### Opción 4: Modo Log (Actual - Solo para desarrollo)

Tu configuración actual guarda los correos en logs en lugar de enviarlos:
```env
MAIL_MAILER=log
```

Los correos se guardan en: `storage/logs/laravel.log`

## 🧪 Probar el Envío de Correos

### 1. Crear una cita de prueba desde el frontend

### 2. Verificar los logs:
```bash
# Ver los últimos logs
tail -f storage/logs/laravel.log

# O en Windows
type storage\logs\laravel.log
```

### 3. Buscar en los logs:
- `"Intentando enviar correo de confirmación"` - Indica que se intentó enviar
- `"Correo enviado exitosamente"` - Confirmación de envío exitoso
- `"Error enviando correo"` - Si hubo algún error

## 📝 Personalización del Correo

El template del correo está en:
```
backend/resources/views/emails/booking/confirmation.blade.php
```

### Variables disponibles en el template:
- `$booking->customer_name` - Nombre del cliente
- `$booking->customer_email` - Email del cliente
- `$booking->start_at` - Fecha/hora de inicio
- `$booking->end_at` - Fecha/hora de fin
- `$booking->service->name` - Nombre del servicio
- `$booking->service->duration_minutes` - Duración del servicio
- `$booking->service->business->name` - Nombre del negocio
- `$booking->employee->name` - Nombre del empleado (si está asignado)

### Agregar información adicional del negocio:

Para incluir teléfono y dirección en los correos, puedes guardarlos en el campo `settings` del negocio:

```json
{
  "phone": "+57 300 123 4567",
  "address": "Calle 123 #45-67, Bogotá",
  "email": "contacto@negocio.com"
}
```

Estos se mostrarán automáticamente en el correo si están configurados.

## 🚀 Comandos Útiles

```bash
# Limpiar cache de configuración
php artisan config:clear

# Ver las configuraciones de mail actuales
php artisan tinker
>>> config('mail')

# Probar envío manual de correo (en tinker)
php artisan tinker
>>> $booking = App\Models\Booking::first();
>>> Mail::to('test@example.com')->send(new App\Mail\BookingConfirmationMail($booking));
```

## 🔍 Troubleshooting

### El correo no se envía:
1. Verifica que `MAIL_MAILER` no esté en `log`
2. Revisa las credenciales SMTP
3. Verifica que el firewall permita conexiones SMTP
4. Revisa los logs: `storage/logs/laravel.log`

### Error de autenticación:
- Gmail: Verifica que uses contraseña de aplicación, no tu contraseña normal
- Verifica que la verificación en dos pasos esté activa

### El correo llega a spam:
- Configura SPF y DKIM en tu dominio
- Usa un servicio profesional (SendGrid, Mailgun)
- Verifica que `MAIL_FROM_ADDRESS` use un dominio válido

## 📱 Próximas Mejoras Sugeridas

1. **Recordatorio de cita:** Enviar correo 24h antes de la cita
2. **Correo de cancelación:** Notificar cuando se cancela una cita
3. **Correo al negocio:** Notificar al dueño cuando hay nueva reserva
4. **Templates personalizados:** Permitir que cada negocio personalice sus correos
5. **Notificaciones por WhatsApp:** Integración con API de WhatsApp Business

## 📚 Recursos

- [Laravel Mail Documentation](https://laravel.com/docs/10.x/mail)
- [Markdown Mailables](https://laravel.com/docs/10.x/mail#markdown-mailables)
- [Mailtrap](https://mailtrap.io/)
- [SendGrid](https://sendgrid.com/)
