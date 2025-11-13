<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;    
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\EmployeeController;

// Autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas públicas
Route::get('/businesses/slug/{slug}', [BusinessController::class, 'showBySlug']);
Route::get('/businesses/{businessId}/employees', [EmployeeController::class, 'publicIndex']);
Route::get('/businesses/{businessId}/employees/{employeeId}/availability', [EmployeeController::class, 'getAvailability']);
Route::get('/businesses/{businessId}/availability', [BookingController::class, 'getAvailability']);
Route::post('/bookings', [BookingController::class, 'store']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Businesses
    Route::get('/businesses', [BusinessController::class, 'index']);
    Route::post('/businesses', [BusinessController::class, 'store']);
    Route::get('/businesses/{id}', [BusinessController::class, 'show']);
    Route::delete('/businesses/{id}', [BusinessController::class, 'destroy']);

    // Services
    Route::get('/businesses/{businessId}/services', [ServiceController::class, 'index']);
    Route::post('/businesses/{businessId}/services', [ServiceController::class, 'store']);
    Route::put('/businesses/{businessId}/services/{serviceId}', [ServiceController::class, 'update']);
    Route::delete('/businesses/{businessId}/services/{serviceId}', [ServiceController::class, 'destroy']);

    // Schedules
    Route::get('/businesses/{businessId}/schedules', [ScheduleController::class, 'index']);
    Route::post('/businesses/{businessId}/schedules', [ScheduleController::class, 'store']);
    Route::put('/businesses/{businessId}/schedules/{scheduleId}', [ScheduleController::class, 'update']);
    Route::delete('/businesses/{businessId}/schedules/{scheduleId}', [ScheduleController::class, 'destroy']);

    // Employees
    Route::get('/businesses/{businessId}/employees/admin', [EmployeeController::class, 'index']);
    Route::post('/businesses/{businessId}/employees', [EmployeeController::class, 'store']);
    Route::get('/businesses/{businessId}/employees/{employeeId}', [EmployeeController::class, 'show']);
    Route::put('/businesses/{businessId}/employees/{employeeId}', [EmployeeController::class, 'update']);
    Route::delete('/businesses/{businessId}/employees/{employeeId}', [EmployeeController::class, 'destroy']);

    // Page Builder
    Route::get('/businesses/{businessId}/page', [PageController::class, 'show']);
    Route::put('/businesses/{businessId}/page/blocks', [PageController::class, 'updateBlocks']);
    Route::delete('/businesses/{businessId}/page/blocks/{blockId}', [PageController::class, 'deleteBlock']);

    // Superadmin routes
    Route::middleware('is_superadmin')->prefix('superadmin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Api\SuperAdminController::class, 'index']);
        Route::post('/users', [\App\Http\Controllers\Api\SuperAdminController::class, 'store']);
        Route::put('/users/{id}/page_limit', [\App\Http\Controllers\Api\SuperAdminController::class, 'updatePageLimit']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\SuperAdminController::class, 'destroy']);
        
        // Pages management
        Route::get('/pages', [\App\Http\Controllers\Api\SuperAdminController::class, 'listPages']);
        Route::post('/pages/toggle', [\App\Http\Controllers\Api\SuperAdminController::class, 'togglePages']);
        Route::post('/users/{userId}/pages/disable', [\App\Http\Controllers\Api\SuperAdminController::class, 'disableUserPages']);
        Route::post('/users/{userId}/pages/enable', [\App\Http\Controllers\Api\SuperAdminController::class, 'enableUserPages']);
    });

    // Bookings (Admin)
    Route::get('/businesses/{businessId}/bookings', [BookingController::class, 'index']);
    Route::put('/businesses/{businessId}/bookings/{bookingId}', [BookingController::class, 'update']);
    Route::delete('/businesses/{businessId}/bookings/{bookingId}', [BookingController::class, 'cancel']);
});