<?php

use App\Modules\Student\Http\Controllers\StudentAcademicController;
use App\Modules\Student\Http\Controllers\StudentController;
use App\Modules\Student\Http\Controllers\StudentDocumentController;
use App\Modules\Student\Http\Controllers\StudentIdConfigController;
use App\Modules\Student\Http\Controllers\TransferCertificateController;
use App\Modules\Student\Http\Controllers\WaitlistController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'ability:admin:*,accountant:*,teacher:*'])->prefix('v2/students')->group(function (): void {

    // ── Core student CRUD ──────────────────────────────────────────────────────
    Route::get('/', [StudentController::class, 'index']);
    Route::post('/', [StudentController::class, 'store']);
    Route::get('/{id}', [StudentController::class, 'show']);
    Route::put('/{id}', [StudentController::class, 'update']);
    Route::delete('/{id}', [StudentController::class, 'destroy']);

    // ── Status transitions (accountant + admin) ────────────────────────────────
    Route::middleware('ability:admin:*,accountant:*')->group(function (): void {
        Route::post('/{id}/transfer', [StudentController::class, 'transfer']);
        Route::post('/{id}/re-admit', [StudentController::class, 'reAdmit']);
    });

    // ── Academic history + promotion ───────────────────────────────────────────
    Route::get('/{studentId}/academics', [StudentAcademicController::class, 'index']);
    Route::post('/{studentId}/academics/promote', [StudentAcademicController::class, 'promote']);

    // ── Documents ─────────────────────────────────────────────────────────────
    Route::get('/{studentId}/documents', [StudentDocumentController::class, 'index']);
    Route::post('/{studentId}/documents', [StudentDocumentController::class, 'store']);
    Route::delete('/{studentId}/documents/{documentId}', [StudentDocumentController::class, 'destroy']);

    // ── Transfer certificates ──────────────────────────────────────────────────
    Route::get('/{studentId}/tcs', [TransferCertificateController::class, 'index']);
    Route::post('/tcs/{id}/issue', [TransferCertificateController::class, 'issue']);
    Route::get('/tcs/{id}/preview', [TransferCertificateController::class, 'preview']);
});

// ── TC Templates (admin only) ─────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/students')->group(function (): void {
    Route::get('/tc-templates', [TransferCertificateController::class, 'indexTemplates']);
    Route::post('/tc-templates', [TransferCertificateController::class, 'storeTemplate']);
    Route::put('/tc-templates/{id}', [TransferCertificateController::class, 'updateTemplate']);
    Route::delete('/tc-templates/{id}', [TransferCertificateController::class, 'destroyTemplate']);
});

// ── Waitlist ──────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,accountant:*'])->prefix('v2/waitlist')->group(function (): void {
    Route::get('/', [WaitlistController::class, 'index']);
    Route::post('/', [WaitlistController::class, 'store']);
    Route::put('/{id}', [WaitlistController::class, 'update']);
    Route::post('/{id}/cancel', [WaitlistController::class, 'cancel']);
});

// ── Student ID config (admin only) ────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/settings')->group(function (): void {
    Route::get('/student-id-config', [StudentIdConfigController::class, 'show']);
    Route::post('/student-id-config', [StudentIdConfigController::class, 'upsert']);
});
