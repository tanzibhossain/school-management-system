<?php

use App\Modules\FeeItem\Http\Controllers\FeeCategoryController;
use App\Modules\FeeItem\Http\Controllers\FeeDiscountController;
use App\Modules\FeeItem\Http\Controllers\FeeItemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FeeItem Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'ability:admin:*,accountant:*'])->group(function (): void {

    // ── Fee Categories ───────────────────────────────────────────────────
    Route::prefix('v2/fee-categories')->group(function (): void {
        Route::get('/', [FeeCategoryController::class, 'index']);
        Route::post('/', [FeeCategoryController::class, 'store']);
        Route::put('/{id}', [FeeCategoryController::class, 'update']);
        Route::delete('/{id}', [FeeCategoryController::class, 'destroy']);
    });

    // ── Fee Discounts ────────────────────────────────────────────────────
    Route::prefix('v2/fee-discounts')->group(function (): void {
        Route::get('/', [FeeDiscountController::class, 'index']);
        Route::post('/', [FeeDiscountController::class, 'store']);
        Route::put('/{id}', [FeeDiscountController::class, 'update']);
        Route::delete('/{id}', [FeeDiscountController::class, 'destroy']);
    });

    // ── Fee Items — /duplicate must be before /{id} ──────────────────────
    Route::prefix('v2/fee-items')->group(function (): void {
        Route::post('/duplicate', [FeeItemController::class, 'duplicate']);
        Route::get('/', [FeeItemController::class, 'index']);
        Route::post('/', [FeeItemController::class, 'store']);
        Route::get('/{id}', [FeeItemController::class, 'show']);
        Route::put('/{id}', [FeeItemController::class, 'update']);
        Route::delete('/{id}', [FeeItemController::class, 'destroy']);
    });
});
