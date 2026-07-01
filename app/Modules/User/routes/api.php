<?php

use App\Modules\User\Http\Controllers\AuthController;
use App\Modules\User\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Public auth endpoints ─────────────────────────────────────────────────────
Route::prefix('v2/auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
});

// ── Authenticated endpoints (any valid token) ─────────────────────────────────
Route::middleware('auth:sanctum')->prefix('v2/auth')->group(function (): void {
    Route::get ('me',              [AuthController::class, 'me']);
    Route::post('logout',          [AuthController::class, 'logout']);
    Route::post('logout-all',      [AuthController::class, 'logoutAll']);
    Route::put ('password',        [AuthController::class, 'changePassword']);
    Route::get ('devices',         [AuthController::class, 'devices']);
    Route::delete('devices/{tokenId}', [AuthController::class, 'revokeDevice']);
    Route::get ('login-history',   [AuthController::class, 'loginHistory']);
});

// ── Admin-only user management ────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:*'])->prefix('v2/admin')->group(function (): void {
    Route::get   ('users',                     [UserController::class, 'index']);
    Route::post  ('users',                     [UserController::class, 'store']);
    Route::get   ('users/{id}',                [UserController::class, 'show']);
    Route::put   ('users/{id}',                [UserController::class, 'update']);
    Route::delete('users/{id}',                [UserController::class, 'destroy']);
    Route::post  ('users/{id}/role',           [UserController::class, 'changeRole']);
    Route::get   ('users/{id}/login-history',  [UserController::class, 'loginHistory']);
    Route::get   ('login-history',             [UserController::class, 'allLoginHistory']);
});
