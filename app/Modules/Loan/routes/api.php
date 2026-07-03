<?php

use App\Modules\Loan\Http\Controllers\StaffLoanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Loan Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

// ── Submission + own history — staff, admin, accountant ──────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,accountant:*,staff:*'])->prefix('v2/loans')->group(function (): void {
    Route::post('/{staffId}', [StaffLoanController::class, 'store'])->whereNumber('staffId');
    Route::get('/{staffId}', [StaffLoanController::class, 'index'])->whereNumber('staffId');
    Route::patch('/{id}/cancel', [StaffLoanController::class, 'cancel'])->whereNumber('id');
});

// ── Approval queue + decisions — admin/accountant only ────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,accountant:*'])->prefix('v2/loans')->group(function (): void {
    Route::get('/pending', [StaffLoanController::class, 'pending']);
    Route::patch('/{id}/approve', [StaffLoanController::class, 'approve'])->whereNumber('id');
    Route::patch('/{id}/reject', [StaffLoanController::class, 'reject'])->whereNumber('id');
});
