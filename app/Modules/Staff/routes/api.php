<?php

use App\Modules\Staff\Http\Controllers\DepartmentController;
use App\Modules\Staff\Http\Controllers\DesignationController;
use App\Modules\Staff\Http\Controllers\StaffAcademicController;
use App\Modules\Staff\Http\Controllers\StaffController;
use App\Modules\Staff\Http\Resources\StaffResource;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

// ── Designations & Departments ────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/designations')->group(function (): void {
    Route::get('/', [DesignationController::class, 'index']);
    Route::post('/', [DesignationController::class, 'store']);
    Route::put('/{id}', [DesignationController::class, 'update']);
    Route::delete('/{id}', [DesignationController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/departments')->group(function (): void {
    Route::get('/', [DepartmentController::class, 'index']);
    Route::post('/', [DepartmentController::class, 'store']);
    Route::put('/{id}', [DepartmentController::class, 'update']);
    Route::delete('/{id}', [DepartmentController::class, 'destroy']);
});

// ── Staff CRUD + transitions ──────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/staff')->group(function (): void {
    Route::get('/', [StaffController::class, 'index']);
    Route::post('/', [StaffController::class, 'store']);
    Route::get('/{id}', [StaffController::class, 'show']);
    Route::put('/{id}', [StaffController::class, 'update']);
    Route::delete('/{id}', [StaffController::class, 'destroy']);

    Route::post('/{id}/terminate', [StaffController::class, 'terminate']);
    Route::post('/{id}/re-hire', [StaffController::class, 'reHire']);

    // Class assignments
    Route::get('/{staffId}/academics', [StaffAcademicController::class, 'index']);
    Route::post('/{staffId}/academics', [StaffAcademicController::class, 'store']);
    Route::delete('/{staffId}/academics/{academicId}', [StaffAcademicController::class, 'destroy']);
});

// ── Staff self-profile (teacher / staff role) ─────────────────────────────
Route::middleware(['auth:sanctum', 'ability:teacher:*,staff:*,admin:*'])->prefix('v2/staff')->group(function (): void {
    Route::get('/me/profile', function (): JsonResponse {
        $user = request()->user();
        $staff = Staff::where('school_id', app('current_school_id'))
            ->where('user_id', $user->id)
            ->with(['designation', 'department', 'academics', 'addresses'])
            ->firstOrFail();

        return (new StaffResource($staff))->response();
    });
});
