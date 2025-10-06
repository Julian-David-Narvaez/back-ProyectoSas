<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\WorkingHourController;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/

// Autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Ver landing page pública
Route::get('/pages/{slug}', [PageController::class, 'publicShow']);

// Ver negocio público
Route::get('/businesses/{slug}', [BusinessController::class, 'getBySlug']);

// Ver servicios públicos
Route::get('/businesses/{slug}/services', [ServiceController::class, 'publicIndex']);

// Obtener horarios disponibles
Route::get('/businesses/{slug}/available-slots', [AppointmentController::class, 'availableSlots']);

// Reservar cita (público)
Route::post('/appointments/book', [AppointmentController::class, 'book']);

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (requieren autenticación)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Gestión de negocios
    Route::apiResource('businesses', BusinessController::class);
    
    // Gestión de servicios
    Route::get('businesses/{business}/services', [ServiceController::class, 'index']);
    Route::post('businesses/{business}/services', [ServiceController::class, 'store']);
    Route::get('businesses/{business}/services/{service}', [ServiceController::class, 'show']);
    Route::put('businesses/{business}/services/{service}', [ServiceController::class, 'update']);
    Route::delete('businesses/{business}/services/{service}', [ServiceController::class, 'destroy']);
    
    // Gestión de horarios de trabajo
    Route::get('businesses/{business}/working-hours', [WorkingHourController::class, 'index']);
    Route::post('businesses/{business}/working-hours', [WorkingHourController::class, 'store']);
    Route::post('businesses/{business}/working-hours/bulk', [WorkingHourController::class, 'bulkStore']);
    Route::put('businesses/{business}/working-hours/{workingHour}', [WorkingHourController::class, 'update']);
    Route::delete('businesses/{business}/working-hours/{workingHour}', [WorkingHourController::class, 'destroy']);
    
    // Gestión de citas
    Route::get('businesses/{business}/appointments', [AppointmentController::class, 'index']);
    Route::get('businesses/{business}/appointments/statistics', [AppointmentController::class, 'statistics']);
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
    
    // Gestión de páginas
    Route::get('businesses/{business}/page', [PageController::class, 'show']);
    Route::put('businesses/{business}/page', [PageController::class, 'update']);
});