#!/bin/bash

echo "🔍 Estado del Sistema de Colas"
echo "================================"
echo ""

echo "📊 Trabajos Pendientes en Cola:"
php artisan queue:monitor database
echo ""

echo "❌ Trabajos Fallidos:"
php artisan queue:failed
echo ""

echo "📈 Estadísticas de la Cola:"
php artisan tinker --execute="
    \$pending = DB::table('jobs')->count();
    \$failed = DB::table('failed_jobs')->count();
    echo \"✅ Trabajos pendientes: \$pending\n\";
    echo \"❌ Trabajos fallidos: \$failed\n\";
"
echo ""

echo "💡 Comandos útiles:"
echo "  - Ver cola en tiempo real:     php artisan queue:work --verbose"
echo "  - Reintentar fallidos:          php artisan queue:retry all"
echo "  - Limpiar trabajos fallidos:    php artisan queue:flush"
echo "  - Enviar correo de prueba:      php artisan email:test tu@email.com"
