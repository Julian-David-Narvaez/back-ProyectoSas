@echo off
echo ========================================
echo   PRUEBA DE ENVIO DE CORREOS
echo ========================================
echo.

cd c:\laragon\www\back-ProyectoSas\backend

echo [1/3] Limpiando cache de configuracion...
call php artisan config:clear
call php artisan cache:clear
echo.

echo [2/3] Verificando configuracion de correo...
echo MAIL_MAILER actual:
findstr "MAIL_MAILER" .env
echo.

echo [3/3] Enviando correo de prueba...
echo.
set /p email="Ingresa tu email para recibir el correo de prueba: "
echo.

call php artisan email:test-booking --email=%email%

echo.
echo ========================================
echo   PROCESO COMPLETADO
echo ========================================
echo.
echo Si configuraste SMTP, revisa tu bandeja de entrada.
echo Si usas modo log, revisa: storage\logs\laravel.log
echo.
pause
