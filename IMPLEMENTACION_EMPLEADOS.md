# Guía de Implementación del Sistema de Empleados

## 🎯 Resumen
Se ha implementado un sistema completo de gestión de empleados/barberos que permite:
- Agregar, editar y eliminar empleados
- Asignar horarios específicos a cada empleado
- Los clientes pueden seleccionar el empleado de su preferencia al hacer una reserva
- Cada empleado tiene sus propios horarios y disponibilidad

## 📦 Cambios Realizados

### Backend (Laravel)

#### 1. Nuevas Migraciones
Se crearon 3 nuevas migraciones:
- `2025_11_13_000001_create_employees_table.php` - Tabla de empleados
- `2025_11_13_000002_add_employee_id_to_schedules_table.php` - Relación empleado-horarios
- `2025_11_13_000003_add_employee_id_to_bookings_table.php` - Relación empleado-reservas

#### 2. Nuevo Modelo
- `app/Models/Employee.php` - Modelo de empleado con relaciones

#### 3. Nuevo Controlador
- `app/Http/Controllers/Api/EmployeeController.php` - CRUD completo de empleados

#### 4. Modelos Actualizados
- `app/Models/Business.php` - Agregada relación `employees()`
- `app/Models/Schedule.php` - Agregada relación `employee()`
- `app/Models/Booking.php` - Agregada relación `employee()`

#### 5. Controladores Actualizados
- `app/Http/Controllers/Api/ScheduleController.php` - Soporte para employee_id
- `app/Http/Controllers/Api/BookingController.php` - Filtrado por empleado en disponibilidad y reservas

#### 6. Rutas API
- `routes/api.php` - Nuevas rutas para gestión de empleados

### Frontend (React)

#### 1. Nuevo Componente
- `src/pages/admin/EmployeesList.jsx` - Gestión de empleados

#### 2. Componente Actualizado
- `src/pages/public/BookingFlow.jsx` - Ahora incluye paso de selección de empleado

#### 3. Rutas Actualizadas
- `src/App.jsx` - Ruta `/admin/business/:businessId/employees`

#### 4. Navegación Actualizada
- `src/pages/admin/ServicesList.jsx` - Botón de acceso a empleados

## 🚀 Pasos para Ejecutar

### Backend

1. **Ejecutar las migraciones:**
   ```bash
   cd c:\laragon\www\back-ProyectoSas\backend
   php artisan migrate
   ```

2. **Verificar que las tablas se crearon correctamente:**
   - `employees`
   - Columna `employee_id` en `schedules`
   - Columna `employee_id` en `bookings`

3. **Limpiar caché (opcional):**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

### Frontend

No requiere cambios adicionales, los archivos están listos.

## 📋 Flujo de Uso

### Para el Dueño del Negocio:

1. **Gestionar Empleados:**
   - Ir a Dashboard → Seleccionar Negocio → Botón "👥 Empleados"
   - Agregar empleados con nombre, email, teléfono
   - Marcar empleados como activos/inactivos
   - Ordenar empleados

2. **Asignar Horarios a Empleados:**
   - En la sección de Horarios, ahora se puede seleccionar un empleado
   - Cada empleado puede tener sus propios horarios de trabajo

### Para el Cliente:

1. **Reservar con Empleado Específico:**
   - Paso 1: Seleccionar servicio (corte de pelo)
   - Paso 2: **NUEVO** - Seleccionar barbero/empleado preferido (o "Sin preferencia")
   - Paso 3: Seleccionar fecha
   - Paso 4: Seleccionar hora disponible del empleado
   - Paso 5: Ingresar datos personales
   - Paso 6: Confirmación

## 🔍 Endpoints API Nuevos

### Públicos
- `GET /api/businesses/{businessId}/employees` - Listar empleados activos
- `GET /api/businesses/{businessId}/employees/{employeeId}/availability` - Disponibilidad de un empleado

### Protegidos (requieren autenticación)
- `GET /api/businesses/{businessId}/employees/admin` - Listar todos los empleados (admin)
- `POST /api/businesses/{businessId}/employees` - Crear empleado
- `GET /api/businesses/{businessId}/employees/{employeeId}` - Ver empleado
- `PUT /api/businesses/{businessId}/employees/{employeeId}` - Actualizar empleado
- `DELETE /api/businesses/{businessId}/employees/{employeeId}` - Eliminar empleado

### Actualizados
- `GET /api/businesses/{businessId}/availability?employee_id=X` - Ahora acepta employee_id opcional
- `POST /api/bookings` - Ahora acepta employee_id opcional

## ⚠️ Notas Importantes

1. **Compatibilidad Retroactiva:** 
   - El sistema sigue funcionando si no hay empleados creados
   - La selección de empleado es opcional en el flujo de reserva

2. **Validaciones:**
   - Los horarios verifican disponibilidad por empleado si se especifica
   - Las reservas validan conflictos por empleado

3. **Datos Existentes:**
   - Las reservas y horarios existentes no tienen empleado asignado (NULL)
   - Esto es normal y no afecta el funcionamiento

## 🎨 Características Visuales

- Tarjetas de empleados con iniciales en círculo colorido
- Indicadores de estado (Activo/Inactivo)
- Formulario inline para agregar/editar
- Tabla responsive con acciones
- Integración visual consistente con el diseño existente

## 🐛 Solución de Problemas

Si algo no funciona:

1. Verificar que las migraciones se ejecutaron: `php artisan migrate:status`
2. Verificar rutas: `php artisan route:list | grep employee`
3. Revisar logs: `storage/logs/laravel.log`
4. Limpiar caché del navegador

## 🔄 Próximas Mejoras Posibles

- Fotos de perfil para empleados
- Estadísticas por empleado
- Calificaciones de clientes
- Especialidades por empleado
- Días libres/vacaciones por empleado
