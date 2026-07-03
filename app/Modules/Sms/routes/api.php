<?php

use App\Modules\Sms\Http\Controllers\SmsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sms Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

// ── Manual sends — admin + teacher (a teacher texting their own class) ─────
Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*'])->prefix('v2/sms')->group(function (): void {
    Route::post('/manual', [SmsController::class, 'sendManual']);
});

// ── Due reminders — financial trigger, admin + accountant only ────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,accountant:*'])->prefix('v2/sms')->group(function (): void {
    Route::post('/due-reminders', [SmsController::class, 'sendDueReminders']);
});

// ── Batch/log history + resend — admin only ────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/sms')->group(function (): void {
    Route::get('/batches', [SmsController::class, 'index']);
    Route::get('/batches/{id}', [SmsController::class, 'show'])->whereNumber('id');
    Route::post('/logs/{id}/resend', [SmsController::class, 'resend'])->whereNumber('id');
});
