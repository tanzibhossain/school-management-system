<?php

use App\Modules\DataImport\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DataImport Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
| Bulk student/teacher onboarding via spreadsheet — admin only (bulk data
| creation is high-risk, same posture as IdCard template management).
*/

Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/data-imports')->group(function (): void {
    Route::get('/template', [ImportController::class, 'template']);
    Route::post('/', [ImportController::class, 'store']);
    Route::get('/', [ImportController::class, 'index']);
    Route::get('/{id}', [ImportController::class, 'show'])->whereNumber('id');
});
