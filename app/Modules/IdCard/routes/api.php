<?php

use App\Modules\IdCard\Http\Controllers\IdCardBatchController;
use App\Modules\IdCard\Http\Controllers\IdCardTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| IdCard Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

// ── Templates — admin only ─────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/id-cards')->group(function (): void {
    Route::get('/templates', [IdCardTemplateController::class, 'index']);
    Route::post('/templates', [IdCardTemplateController::class, 'store']);
    Route::put('/templates/{id}', [IdCardTemplateController::class, 'update'])->whereNumber('id');
    Route::delete('/templates/{id}', [IdCardTemplateController::class, 'destroy'])->whereNumber('id');
});

// ── Batches — admin + teacher (student cards only; RequestIdCardBatchRequest
//    narrows staff-type batches to admin-only) ──────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*'])->prefix('v2/id-cards')->group(function (): void {
    Route::post('/batches', [IdCardBatchController::class, 'store']);
    Route::get('/batches', [IdCardBatchController::class, 'index']);
    Route::get('/batches/{id}', [IdCardBatchController::class, 'show'])->whereNumber('id');
});
