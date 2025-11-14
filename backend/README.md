<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# 🗓️ Sistema SaaS de Gestión de Citas

Backend API REST desarrollado con Laravel para un sistema multi-tenant de gestión de citas y reservas.

## ✨ Características Principales

- 🏢 **Multi-tenant**: Cada negocio tiene su propio espacio
- 📅 **Sistema de reservas**: Gestión completa de citas
- 👥 **Gestión de empleados**: Asignación de profesionales
- ⏰ **Horarios personalizables**: Configuración flexible de disponibilidad
- 📧 **Notificaciones por email**: Confirmación automática de citas
- 🎨 **Page Builder**: Constructor de páginas personalizadas
- 🔐 **Autenticación segura**: Laravel Sanctum
- 🌐 **API RESTful**: Documentada y fácil de consumir

## 📧 Sistema de Correos Electrónicos

**✅ Ya está implementado y funcionando**

El sistema envía automáticamente correos de confirmación cuando un cliente agenda una cita.

### 🚀 Activar Envío de Correos

**Ver guía completa**: [`ACTIVAR_CORREOS_AHORA.md`](ACTIVAR_CORREOS_AHORA.md)

**Configuración rápida**:
1. Edita `.env` y cambia `MAIL_MAILER=log` a `MAIL_MAILER=smtp`
2. Configura tus credenciales SMTP (Gmail, Mailtrap, SendGrid)
3. Ejecuta: `php artisan config:clear`
4. Prueba: `php artisan email:test-booking --email=tu-email@gmail.com`

**Documentación disponible**:
- [`CORREOS_LISTOS.md`](CORREOS_LISTOS.md) - Resumen completo del sistema
- [`CONFIGURACION_CORREOS.md`](CONFIGURACION_CORREOS.md) - Guía detallada
- [`ACTIVAR_CORREOS_AHORA.md`](ACTIVAR_CORREOS_AHORA.md) - Guía rápida de activación

## 🛠️ Instalación

```bash
# Clonar repositorio
git clone <repo-url>
cd backend

# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
DB_DATABASE=saas_citas
DB_USERNAME=root
DB_PASSWORD=

# Migrar base de datos
php artisan migrate

# Iniciar servidor
php artisan serve
```

## 📚 Estructura del Proyecto

```
backend/
├── app/
│   ├── Http/Controllers/Api/     # Controladores de la API
│   ├── Models/                   # Modelos Eloquent
│   ├── Mail/                     # Clases de correos
│   └── Console/Commands/         # Comandos artisan
├── database/
│   └── migrations/               # Migraciones de BD
├── resources/
│   └── views/emails/             # Templates de correos
├── routes/
│   └── api.php                   # Rutas de la API
└── tests/                        # Tests automatizados
```

## 🔧 Comandos Útiles

```bash
# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear

# Probar envío de correos
php artisan email:test-booking --email=tu-email@gmail.com

# Ejecutar tests
php artisan test

# Ver logs
Get-Content storage\logs\laravel.log -Tail 50
```

## 🌐 API Endpoints

### Autenticación
- `POST /api/register` - Registro de usuario
- `POST /api/login` - Inicio de sesión
- `POST /api/logout` - Cerrar sesión

### Negocios
- `GET /api/businesses` - Listar negocios
- `POST /api/businesses` - Crear negocio
- `PUT /api/businesses/{id}` - Actualizar negocio

### Servicios
- `GET /api/businesses/{id}/services` - Listar servicios
- `POST /api/businesses/{id}/services` - Crear servicio

### Reservas (Público)
- `GET /api/businesses/{id}/availability` - Ver disponibilidad
- `POST /api/bookings` - Crear reserva

### Reservas (Admin)
- `GET /api/businesses/{id}/bookings` - Listar reservas
- `PUT /api/businesses/{id}/bookings/{id}` - Actualizar reserva
- `DELETE /api/businesses/{id}/bookings/{id}` - Cancelar reserva

## 🔐 Seguridad

- Autenticación mediante Laravel Sanctum
- Validación de datos en todas las peticiones
- Protección CSRF
- CORS configurado
- Hash seguro de contraseñas

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Test específico de emails
php artisan test --filter=EmailTest

# Con coverage
php artisan test --coverage
```

## 📦 Tecnologías

- **Framework**: Laravel 10.x
- **Base de Datos**: MySQL
- **Autenticación**: Laravel Sanctum
- **Email**: Laravel Mail (SMTP/Log/Mailtrap)
- **Testing**: PHPUnit

## 🚀 Despliegue

### Despliegue en Railway

**Ver guía rápida**: [`RAILWAY_QUICKSTART.md`](RAILWAY_QUICKSTART.md)

**Pasos básicos:**

1. **Crear proyecto en Railway**: https://railway.app/
2. **Conectar repositorio de GitHub**
3. **Agregar MySQL Database**
4. **Configurar variables de entorno** (ver `.env.production.example`)
5. **Generar dominio público**
6. **Push a GitHub** → Railway desplegará automáticamente

### Variables de Entorno Importantes

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=tu-host
DB_DATABASE=tu-database
DB_USERNAME=tu-username
DB_PASSWORD=tu-password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-contraseña-de-aplicación
```

## 📝 Documentación Adicional

- [`IMPLEMENTACION_EMPLEADOS.md`](../IMPLEMENTACION_EMPLEADOS.md) - Gestión de empleados
- [Laravel Documentation](https://laravel.com/docs/10.x)

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT.

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
