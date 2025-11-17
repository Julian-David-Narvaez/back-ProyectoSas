@echo off
echo ===============================================
echo       PRUEBA DE ENVIO DE CORREOS CON RESEND
echo ===============================================
echo.

echo 1. Probando SDK de Resend...
echo.

REM Cambiar al directorio del backend
cd /d "c:\laragon\www\back-ProyectoSas\backend"

REM Ejecutar comando de prueba usando Artisan Tinker
echo use App\Services\ResendMailService; | php artisan tinker --execute="
$service = app(App\Services\ResendMailService::class);
$result = $service->sendTestEmail('test@example.com', 'Prueba desde Script');
dump($result);
"

echo.
echo ===============================================
echo 2. Para probar desde el navegador, visita:
echo    http://localhost/api/test-basic-resend?email=tu-email@ejemplo.com
echo ===============================================
echo.

pause