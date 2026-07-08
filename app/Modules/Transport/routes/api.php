<?php

use App\Modules\Transport\Http\Controllers\StudentTransportAssignmentController;
use App\Modules\Transport\Http\Controllers\TransportDriverController;
use App\Modules\Transport\Http\Controllers\TransportRouteController;
use App\Modules\Transport\Http\Controllers\TransportVehicleController;
use Illuminate\Support\Facades\Route;

// Admin-only surface: routes, vehicles, drivers, swaps.
Route::middleware(['auth:sanctum', 'ability:admin:*', 'module.enabled:transport'])
    ->prefix('v2/transport')
    ->group(function (): void {
        // Routes
        Route::get('/routes', [TransportRouteController::class, 'index']);
        Route::post('/routes', [TransportRouteController::class, 'store']);
        Route::get('/routes/{id}', [TransportRouteController::class, 'show'])->whereNumber('id');
        Route::put('/routes/{id}', [TransportRouteController::class, 'update'])->whereNumber('id');
        Route::delete('/routes/{id}', [TransportRouteController::class, 'destroy'])->whereNumber('id');
        Route::put('/routes/{id}/vehicle', [TransportRouteController::class, 'setVehicle'])->whereNumber('id');
        Route::post('/routes/{id}/swap-vehicle', [TransportRouteController::class, 'swapVehicle'])->whereNumber('id');

        // Vehicles
        Route::get('/vehicles', [TransportVehicleController::class, 'index']);
        Route::post('/vehicles', [TransportVehicleController::class, 'store']);
        Route::get('/vehicles/{id}', [TransportVehicleController::class, 'show'])->whereNumber('id');
        Route::put('/vehicles/{id}', [TransportVehicleController::class, 'update'])->whereNumber('id');
        Route::patch('/vehicles/{id}/status', [TransportVehicleController::class, 'changeStatus'])->whereNumber('id');
        Route::delete('/vehicles/{id}', [TransportVehicleController::class, 'destroy'])->whereNumber('id');

        // Drivers
        Route::get('/drivers', [TransportDriverController::class, 'index']);
        Route::post('/drivers', [TransportDriverController::class, 'store']);
        Route::get('/drivers/{id}', [TransportDriverController::class, 'show'])->whereNumber('id');
        Route::put('/drivers/{id}', [TransportDriverController::class, 'update'])->whereNumber('id');
        Route::delete('/drivers/{id}', [TransportDriverController::class, 'destroy'])->whereNumber('id');
    });

// Admin + accountant read: route roster.
Route::middleware(['auth:sanctum', 'ability:admin:*,accountant:*', 'module.enabled:transport'])
    ->prefix('v2/transport')
    ->group(function (): void {
        Route::get('/routes/{id}/roster', [TransportRouteController::class, 'roster'])->whereNumber('id');
    });

// Admin + receptionist: student assignments.
Route::middleware(['auth:sanctum', 'ability:admin:*,receptionist:*', 'module.enabled:transport'])
    ->prefix('v2/transport')
    ->group(function (): void {
        Route::get('/assignments', [StudentTransportAssignmentController::class, 'index']);
        Route::post('/assignments', [StudentTransportAssignmentController::class, 'store']);
        Route::patch('/assignments/{id}/end', [StudentTransportAssignmentController::class, 'end'])->whereNumber('id');
    });
