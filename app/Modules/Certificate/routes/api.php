<?php

use App\Modules\Certificate\Http\Controllers\AdmitCardController;
use App\Modules\Certificate\Http\Controllers\TestimonialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Certificate Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

// ── Admit cards — teachers + admins ───────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*'])->prefix('v2/certificates')->group(function (): void {
    Route::post('/admit-cards/{studentId}', [AdmitCardController::class, 'store'])->whereNumber('studentId');
    Route::get('/admit-cards/{studentId}', [AdmitCardController::class, 'index'])->whereNumber('studentId');
});

// ── Testimonials + templates — admin only ─────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/certificates')->group(function (): void {
    Route::get('/testimonial-templates', [TestimonialController::class, 'indexTemplates']);
    Route::post('/testimonial-templates', [TestimonialController::class, 'storeTemplate']);
    Route::put('/testimonial-templates/{id}', [TestimonialController::class, 'updateTemplate'])->whereNumber('id');
    Route::delete('/testimonial-templates/{id}', [TestimonialController::class, 'destroyTemplate'])->whereNumber('id');

    Route::post('/testimonials/{studentId}', [TestimonialController::class, 'store'])->whereNumber('studentId');
    Route::get('/testimonials/{studentId}', [TestimonialController::class, 'index'])->whereNumber('studentId');
    Route::post('/testimonials/{id}/issue', [TestimonialController::class, 'issue'])->whereNumber('id');
    Route::get('/testimonials/{id}/preview', [TestimonialController::class, 'preview'])->whereNumber('id');
});
