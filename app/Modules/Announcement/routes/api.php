<?php

use App\Modules\Announcement\Http\Controllers\AnnouncementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Announcement Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

// ── Portal users: feed + mark-read — MUST be registered before /{id} ────
Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*,staff:*,student:*,accountant:*'])->prefix('v2/announcements')->group(function (): void {
    Route::get('/feed', [AnnouncementController::class, 'feed']);
    Route::post('/{id}/read', [AnnouncementController::class, 'markRead']);
});

// ── Admin: full CRUD + publish controls ──────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/announcements')->group(function (): void {
    Route::get('/', [AnnouncementController::class, 'index']);
    Route::post('/', [AnnouncementController::class, 'store']);
    Route::get('/{id}', [AnnouncementController::class, 'show']);
    Route::put('/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/{id}', [AnnouncementController::class, 'destroy']);

    Route::post('/{id}/publish', [AnnouncementController::class, 'publish']);
    Route::post('/{id}/schedule', [AnnouncementController::class, 'schedule']);
    Route::post('/{id}/expire', [AnnouncementController::class, 'expire']);
});
