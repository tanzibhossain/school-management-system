<?php

use App\Modules\OnlineAdmission\Http\Controllers\AdmissionApplicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| OnlineAdmission Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

// ── Public — no login, throttled (applicant has no account) ─────────────────
Route::middleware(['throttle:10,1'])->prefix('v2/admission-applications')->group(function (): void {
    Route::post('/', [AdmissionApplicationController::class, 'store']);
    Route::get('/status', [AdmissionApplicationController::class, 'status']);
});

// ── Staff review queue — admin only ─────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/admission-applications')->group(function (): void {
    Route::get('/', [AdmissionApplicationController::class, 'index']);
    Route::get('/{id}', [AdmissionApplicationController::class, 'show'])->whereNumber('id');
    Route::post('/{id}/approve', [AdmissionApplicationController::class, 'approve'])->whereNumber('id');
    Route::post('/{id}/reject', [AdmissionApplicationController::class, 'reject'])->whereNumber('id');
});
