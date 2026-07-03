<?php

use App\Modules\Leave\Http\Controllers\LeaveTypeController;
use App\Modules\Leave\Http\Controllers\StaffLeaveRequestController;
use App\Modules\Leave\Http\Controllers\StudentLeaveRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Leave Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

// ── Leave types — admin only ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/leave')->group(function (): void {
    Route::get('/types', [LeaveTypeController::class, 'index']);
    Route::post('/types', [LeaveTypeController::class, 'store']);
    Route::put('/types/{id}', [LeaveTypeController::class, 'update'])->whereNumber('id');
    Route::delete('/types/{id}', [LeaveTypeController::class, 'destroy'])->whereNumber('id');
});

// ── Student leave — teachers + admins ─────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*'])->prefix('v2/leave')->group(function (): void {
    Route::get('/students/pending', [StudentLeaveRequestController::class, 'pendingForSection']);
    Route::post('/students/{studentId}', [StudentLeaveRequestController::class, 'store'])->whereNumber('studentId');
    Route::get('/students/{studentId}', [StudentLeaveRequestController::class, 'index'])->whereNumber('studentId');
    Route::patch('/students/{id}/approve', [StudentLeaveRequestController::class, 'approve'])->whereNumber('id');
    Route::patch('/students/{id}/reject', [StudentLeaveRequestController::class, 'reject'])->whereNumber('id');
    Route::patch('/students/{id}/cancel', [StudentLeaveRequestController::class, 'cancel'])->whereNumber('id');
});

// ── Staff leave — submission open to staff + admins ───────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,staff:*'])->prefix('v2/leave')->group(function (): void {
    Route::post('/staff/{staffId}', [StaffLeaveRequestController::class, 'store'])->whereNumber('staffId');
    Route::get('/staff/{staffId}', [StaffLeaveRequestController::class, 'index'])->whereNumber('staffId');
    Route::patch('/staff/{id}/cancel', [StaffLeaveRequestController::class, 'cancel'])->whereNumber('id');
});

// ── Staff leave — approval queue + decisions are admin-only (no manager field on Staff yet) ─
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/leave')->group(function (): void {
    Route::get('/staff/pending', [StaffLeaveRequestController::class, 'pending']);
    Route::patch('/staff/{id}/approve', [StaffLeaveRequestController::class, 'approve'])->whereNumber('id');
    Route::patch('/staff/{id}/reject', [StaffLeaveRequestController::class, 'reject'])->whereNumber('id');
});
