<?php

use App\Modules\Report\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Report Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
| All three reports touch financial data (Payment), so access is
| admin+accountant only — not teacher-accessible, matching Payment/Loan's
| convention (FormRequest::authorize() enforces this per-report, same
| pattern as the rest of the codebase).
*/

Route::middleware(['auth:sanctum', 'ability:admin:*,accountant:*'])->prefix('v2/reports')->group(function (): void {
    Route::get('/fee-collection', [ReportController::class, 'feeCollection']);
    Route::get('/outstanding-dues', [ReportController::class, 'outstandingDues']);
    Route::get('/students/{studentId}/ledger', [ReportController::class, 'studentLedger'])->whereNumber('studentId');
});
