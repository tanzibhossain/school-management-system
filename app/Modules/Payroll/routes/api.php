<?php

use App\Modules\Payroll\Http\Controllers\PayrollRunController;
use App\Modules\Payroll\Http\Controllers\PayslipController;
use App\Modules\Payroll\Http\Controllers\SalaryCertificateController;
use App\Modules\Payroll\Http\Controllers\SalaryComponentController;
use App\Modules\Payroll\Http\Controllers\StaffSalaryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payroll Module API Routes
|--------------------------------------------------------------------------
| Gated admin:*,accountant:* (matches Payment/Report/Loan's convention — the
| DevPlan's "Finance"/"Head Teacher" roles don't exist in this app's
| RoleSeeder). Self-service ("own record") routes are gated on every
| Staff-backed role's wildcard ability instead — see the abilitiesForRole()
| fix in app/Models/User.php.
*/

Route::middleware(['auth:sanctum', 'ability:admin:*,accountant:*', 'module.enabled:payroll'])->prefix('v2/payroll')->group(function (): void {
    // Salary components
    Route::get('/components', [SalaryComponentController::class, 'index']);
    Route::post('/components', [SalaryComponentController::class, 'store']);
    Route::put('/components/{id}', [SalaryComponentController::class, 'update'])->whereNumber('id');
    Route::delete('/components/{id}', [SalaryComponentController::class, 'destroy'])->whereNumber('id');

    // Per-staff salary values
    Route::get('/staff/{staffId}/salary', [StaffSalaryController::class, 'show'])->whereNumber('staffId');
    Route::post('/staff/{staffId}/salary', [StaffSalaryController::class, 'store'])->whereNumber('staffId');

    // Payroll runs
    Route::get('/runs', [PayrollRunController::class, 'index']);
    Route::post('/runs', [PayrollRunController::class, 'store']);
    Route::get('/runs/{id}', [PayrollRunController::class, 'show'])->whereNumber('id');
    Route::post('/runs/{id}/process', [PayrollRunController::class, 'process'])->whereNumber('id');
    Route::post('/runs/{id}/approve', [PayrollRunController::class, 'approve'])->whereNumber('id');

    // Payslips
    Route::post('/entries/{id}/payslip', [PayslipController::class, 'generate'])->whereNumber('id');

    // Salary certificates — Finance-side (list pending + generate)
    Route::get('/salary-certificate', [SalaryCertificateController::class, 'index']);
    Route::post('/salary-certificate/{id}/generate', [SalaryCertificateController::class, 'generate'])->whereNumber('id');
});

// Self-service — own record only, any Staff-backed role (teacher/accountant/librarian/receptionist) or admin.
Route::middleware(['auth:sanctum', 'ability:admin:*,accountant:*,teacher:*,staff:*,librarian:*,receptionist:*', 'module.enabled:payroll'])
    ->prefix('v2/payroll')
    ->group(function (): void {
        Route::get('/staff/me/payslips', [PayslipController::class, 'myPayslips']);
        Route::get('/staff/me/certificates', [SalaryCertificateController::class, 'myCertificates']);
        Route::post('/salary-certificate', [SalaryCertificateController::class, 'store']);
    });
