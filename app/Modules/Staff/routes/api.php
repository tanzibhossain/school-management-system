<?php

use App\Modules\Staff\Http\Controllers\DepartmentController;
use App\Modules\Staff\Http\Controllers\DesignationController;
use App\Modules\Staff\Http\Controllers\StaffAcademicController;
use App\Modules\Staff\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

// ── Designations & Departments (admin only) ───────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->group(function (): void {
    Route::apiResource('designations', DesignationController::class)->except(['show']);
    Route::apiResource('departments', DepartmentController::class)->except(['show']);
});

// ── Staff CRUD ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->group(function (): void {
    Route::apiResource('staff', StaffController::class);
    Route::post('staff/{id}/terminate', [StaffController::class, 'terminate']);
    Route::post('staff/{id}/re-hire', [StaffController::class, 'reHire']);

    // Class assignments
    Route::get('staff/{staffId}/academics', [StaffAcademicController::class, 'index']);
    Route::post('staff/{staffId}/academics', [StaffAcademicController::class, 'store']);
    Route::delete('staff/{staffId}/academics/{academicId}', [StaffAcademicController::class, 'destroy']);
});

// ── Staff self-profile (teacher / staff role) ─────────────────────────────
Route::middleware(['auth:sanctum', 'ability:teacher:*,staff:*,admin:*'])->group(function (): void {
    Route::get('staff/me/profile', function (): \Illuminate\Http\JsonResponse {
        $user  = request()->user();
        $staff = \App\Modules\Staff\Models\Staff::where('school_id', app('current_school_id'))
            ->where('user_id', $user->id)
            ->with(['designation', 'department', 'academics', 'addresses'])
            ->firstOrFail();

        return (new \App\Modules\Staff\Http\Resources\StaffResource($staff))->response();
    });
});
