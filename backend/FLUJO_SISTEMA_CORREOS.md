# 🔄 FLUJO DEL SISTEMA DE CORREOS

## 📊 ANTES (❌ No funcionaba)

```
Frontend (Vercel)
    │
    │ POST /api/bookings
    ↓
Backend (Railway)
    │
    ├─ Crea reserva en DB ✅
    │
    ├─ Intenta encolar correo
    │   │
    │   ├─ QUEUE_CONNECTION=database
    │   │
    │   └─ Inserta en tabla 'jobs' ❌ ERROR: Table doesn't exist!
    │
    └─ Responde 201 ✅

❌ PROBLEMA: No hay tabla 'jobs'
❌ PROBLEMA: No hay worker procesando
❌ RESULTADO: Correos nunca se envían
```

---

## 📊 AHORA (✅ Funciona)

```
Frontend (Vercel)
    │
    │ POST /api/bookings
    ↓
Backend API (Railway)
    │
    ├─ Crea reserva en DB ✅
    │
    ├─ Encola correo
    │   │
    │   ├─ QUEUE_CONNECTION=database
    │   │
    │   └─ Inserta en tabla 'jobs' ✅
    │
    └─ Responde 201 inmediatamente ✅
        (usuario no espera el envío del correo)

Worker (Railway - mismo contenedor o separado)
    │
    ↓ php artisan queue:work
    │
    ├─ Lee tabla 'jobs' cada segundo
    │
    ├─ Encuentra trabajo pendiente
    │
    ├─ Ejecuta BookingConfirmationMail
    │   │
    │   └─ Conecta con Gmail SMTP ✅
    │       │
    │       └─ Envía correo ✅
    │
    └─ Marca trabajo como completado
        │
        └─ Elimina de tabla 'jobs' ✅
```

---

## 🗄️ ESTRUCTURA DE LA BASE DE DATOS

### Tabla: `jobs`
```sql
CREATE TABLE jobs (
    id BIGINT PRIMARY KEY,
    queue VARCHAR(255),
    payload LONGTEXT,          -- Contiene datos del correo serializado
    attempts TINYINT,          -- Intentos realizados
    reserved_at INT,
    available_at INT,
    created_at INT
);
```

**Ejemplo de registro:**
```json
{
  "id": 1,
  "queue": "default",
  "payload": {
    "displayName": "App\\Mail\\BookingConfirmationMail",
    "job": "Illuminate\\Queue\\CallQueuedHandler@call",
    "data": {
      "commandName": "Illuminate\\Mail\\SendQueuedMailable",
      "command": "O:34:\"Illuminate\\Mail\\SendQueuedMailable\":14:{...}"
    }
  },
  "attempts": 0,
  "created_at": 1731600000
}
```

### Tabla: `failed_jobs`
```sql
CREATE TABLE failed_jobs (
    id BIGINT PRIMARY KEY,
    uuid VARCHAR(255),
    connection TEXT,
    queue TEXT,
    payload LONGTEXT,
    exception LONGTEXT,        -- Error que causó el fallo
    failed_at TIMESTAMP
);
```

---

## 🔧 COMPONENTES DEL SISTEMA

### 1. Mailable (BookingConfirmationMail.php)
```php
class BookingConfirmationMail extends Mailable implements ShouldQueue
                                                    // ↑ Esto hace que se encole
{
    use Queueable; // ← Proporciona métodos de cola
    
    public function __construct($booking) {
        $this->booking = $booking->load('service.business', 'employee');
    }
}
```

### 2. Controller (BookingController.php)
```php
// ❌ ANTES: Envío síncrono (lento)
Mail::to($email)->send(new BookingConfirmationMail($booking));

// ✅ AHORA: Envío asíncrono (rápido)
Mail::to($email)->queue(new BookingConfirmationMail($booking));
```

### 3. Worker (Railway)
```bash
# Comando que procesa las colas
php artisan queue:work --tries=3 --timeout=90 --daemon
```

**Parámetros:**
- `--tries=3`: Reintenta 3 veces si falla
- `--timeout=90`: Máximo 90 segundos por trabajo
- `--daemon`: Modo continuo (no se reinicia en cada trabajo)

---

## ⚡ BENEFICIOS DE USAR COLAS

### 1. Respuesta Más Rápida
```
❌ SIN colas:
   Usuario crea reserva → Backend envía correo (2-3 seg) → Responde
   Total: 2-3 segundos

✅ CON colas:
   Usuario crea reserva → Backend encola correo → Responde inmediatamente
   Total: 100-200 milisegundos
```

### 2. Mejor Manejo de Errores
```
❌ SIN colas:
   Error en SMTP → Usuario ve error 500

✅ CON colas:
   Error en SMTP → Reintenta automáticamente 3 veces
   Si falla → Guarda en 'failed_jobs' para revisión
   Usuario nunca ve el error
```

### 3. Escalabilidad
```
❌ SIN colas:
   100 reservas simultáneas = 100 conexiones SMTP simultáneas
   Gmail puede bloquear

✅ CON colas:
   100 reservas simultáneas = 100 registros en 'jobs'
   Worker procesa 1 por 1 de forma ordenada
```

---

## 📈 CICLO DE VIDA DE UN CORREO

```
1. Usuario crea reserva
   ↓
2. BookingController::store()
   ↓
3. Mail::to()->queue(new BookingConfirmationMail($booking))
   ↓
4. Laravel serializa el Mailable
   ↓
5. Inserta en tabla 'jobs'
   ↓
6. Responde al usuario (reserva creada)
   
   ... (unos segundos después) ...
   
7. Worker detecta trabajo en 'jobs'
   ↓
8. Deserializa el Mailable
   ↓
9. Carga datos del booking desde DB
   ↓
10. Renderiza template Blade
    ↓
11. Conecta con Gmail SMTP
    ↓
12. Envía correo
    ↓
13. Gmail confirma envío
    ↓
14. Worker elimina trabajo de 'jobs'
    ↓
15. ✅ Correo entregado
```

---

## 🎯 ESTADOS DE UN TRABAJO

```
┌─────────────┐
│   PENDING   │  ← Recién creado, esperando procesamiento
└──────┬──────┘
       │
       ↓
┌─────────────┐
│ PROCESSING  │  ← Worker lo está procesando
└──────┬──────┘
       │
       ├─── ✅ EXITOSO → Eliminado de 'jobs'
       │
       └─── ❌ ERROR
              │
              ├─── Intento 1/3 → Vuelve a PENDING
              ├─── Intento 2/3 → Vuelve a PENDING
              └─── Intento 3/3 FALLA → Movido a 'failed_jobs'
```

---

## 🔍 LOGS TÍPICOS

### ✅ Flujo Exitoso
```
[2025-11-14 10:15:30] INFO: Encolando correo de confirmación
    booking_id: 123
    email: cliente@example.com

[2025-11-14 10:15:30] INFO: Correo encolado exitosamente
    booking_id: 123

[2025-11-14 10:15:31] INFO: Processing: App\Mail\BookingConfirmationMail
    booking_id: 123

[2025-11-14 10:15:33] INFO: Processed: App\Mail\BookingConfirmationMail
    time: 2.1s
```

### ❌ Flujo con Error
```
[2025-11-14 10:15:30] INFO: Encolando correo de confirmación

[2025-11-14 10:15:31] ERROR: Connection refused [smtp.gmail.com:587]
    attempt: 1/3

[2025-11-14 10:15:35] ERROR: Connection refused [smtp.gmail.com:587]
    attempt: 2/3

[2025-11-14 10:15:40] ERROR: Connection refused [smtp.gmail.com:587]
    attempt: 3/3

[2025-11-14 10:15:40] ERROR: Job moved to failed_jobs table
    error: "Could not connect to SMTP host"
```

---

## 💾 COMANDOS ÚTILES

### Monitoreo
```bash
# Ver cola en tiempo real
php artisan queue:work --verbose

# Ver trabajos fallidos
php artisan queue:failed

# Estadísticas
php artisan tinker --execute="
    echo 'Pendientes: '.DB::table('jobs')->count().PHP_EOL;
    echo 'Fallidos: '.DB::table('failed_jobs')->count().PHP_EOL;
"
```

### Gestión
```bash
# Reintentar todos los fallidos
php artisan queue:retry all

# Reintentar uno específico
php artisan queue:retry 5

# Limpiar trabajos fallidos
php artisan queue:flush

# Eliminar un fallido específico
php artisan queue:forget 5
```

### Testing
```bash
# Enviar correo de prueba
php artisan email:test tu@email.com

# Procesar un solo trabajo y salir
php artisan queue:work --once

# Procesar y mostrar detalles
php artisan queue:work --verbose --once
```

---

## 🎓 CONCEPTOS CLAVE

### Queue (Cola)
- Lista ordenada de trabajos pendientes
- FIFO: First In, First Out
- Almacenada en DB (tabla `jobs`)

### Worker (Trabajador)
- Proceso que ejecuta trabajos de la cola
- Corre en background continuamente
- Puede tener múltiples workers para mayor velocidad

### Job (Trabajo)
- Tarea específica a ejecutar
- En este caso: enviar un correo
- Serializado y guardado en DB

### Failed Job (Trabajo Fallido)
- Trabajo que falló después de todos los reintentos
- Guardado para inspección manual
- Puede ser reintentado manualmente

---

**Este documento explica el flujo completo del sistema de colas y correos.**
