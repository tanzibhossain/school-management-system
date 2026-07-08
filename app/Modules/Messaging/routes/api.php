<?php

use App\Modules\Messaging\Http\Controllers\MessageController;
use App\Modules\Messaging\Http\Controllers\MessagingAdminController;
use App\Modules\Messaging\Http\Controllers\ThreadController;
use Illuminate\Support\Facades\Route;

// Participant surface — any authenticated user; the MessagingPolicyService gates
// who can actually talk to whom.
Route::middleware(['auth:sanctum', 'module.enabled:messaging'])
    ->prefix('v2/messaging')
    ->group(function (): void {
        Route::get('/unread-count', [MessageController::class, 'unreadCount']);

        Route::get('/threads', [ThreadController::class, 'index']);
        Route::post('/threads', [ThreadController::class, 'store']);
        Route::get('/threads/{id}', [ThreadController::class, 'show'])->whereNumber('id');
        Route::post('/threads/{id}/participants', [ThreadController::class, 'addParticipant'])->whereNumber('id');
        Route::delete('/threads/{id}/participants/{userId}', [ThreadController::class, 'removeParticipant'])->whereNumber('id')->whereNumber('userId');

        Route::get('/threads/{id}/messages', [MessageController::class, 'index'])->whereNumber('id');
        Route::post('/threads/{id}/messages', [MessageController::class, 'store'])->whereNumber('id');
        Route::post('/threads/{id}/read', [MessageController::class, 'read'])->whereNumber('id');

        Route::delete('/messages/{id}', [MessageController::class, 'destroy'])->whereNumber('id');
        Route::get('/attachments/{id}', [MessageController::class, 'downloadAttachment'])->whereNumber('id');
    });

// Admin moderation — real Spatie role (not ability:*), read + lock only.
Route::middleware(['auth:sanctum', 'role:admin', 'module.enabled:messaging'])
    ->prefix('v2/messaging/admin')
    ->group(function (): void {
        Route::get('/threads', [MessagingAdminController::class, 'threads']);
        Route::get('/threads/{id}/messages', [MessagingAdminController::class, 'messages'])->whereNumber('id');
        Route::post('/threads/{id}/lock', [MessagingAdminController::class, 'lock'])->whereNumber('id');
    });
