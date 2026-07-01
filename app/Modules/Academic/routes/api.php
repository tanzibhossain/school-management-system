<?php

use App\Modules\Academic\Http\Controllers\AcademicGroupController;
use App\Modules\Academic\Http\Controllers\AcademicPublicController;
use App\Modules\Academic\Http\Controllers\AcademicShiftController;
use App\Modules\Academic\Http\Controllers\AcademicVersionController;
use App\Modules\Academic\Http\Controllers\AcademicYearController;
use App\Modules\Academic\Http\Controllers\ClassRoutineController;
use App\Modules\Academic\Http\Controllers\RoutinePeriodController;
use App\Modules\Academic\Http\Controllers\RoutineRoomController;
use App\Modules\Academic\Http\Controllers\SchoolClassController;
use App\Modules\Academic\Http\Controllers\SectionController;
use App\Modules\Academic\Http\Controllers\StudentTypeController;
use App\Modules\Academic\Http\Controllers\SubjectController;
use App\Modules\Academic\Http\Controllers\TransportController;
use Illuminate\Support\Facades\Route;

// ── Public endpoints (no auth) ────────────────────────────────────────────────
Route::prefix('v2/public/academic')->group(function (): void {
    Route::get('dropdowns',    [AcademicPublicController::class, 'dropdowns']);
    Route::get('years',        [AcademicPublicController::class, 'years']);
    Route::get('classes',      [AcademicPublicController::class, 'classes']);
    Route::get('shifts',       [AcademicPublicController::class, 'shifts']);
    Route::get('versions',     [AcademicPublicController::class, 'versions']);
    Route::get('groups',       [AcademicPublicController::class, 'groups']);
    Route::get('transports',   [AcademicPublicController::class, 'transports']);
    Route::get('student-types',[AcademicPublicController::class, 'studentTypes']);
});

// ── Admin-protected endpoints ─────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:academic'])->prefix('v2/academic')->group(function (): void {

    // Academic years
    Route::apiResource('years', AcademicYearController::class);
    Route::patch('years/{id}/set-current', [AcademicYearController::class, 'setCurrent']);

    // Reference data (simple CRUD)
    Route::apiResource('classes',       SchoolClassController::class);
    Route::apiResource('sections',      SectionController::class);
    Route::apiResource('shifts',        AcademicShiftController::class);
    Route::apiResource('versions',      AcademicVersionController::class);
    Route::apiResource('groups',        AcademicGroupController::class);
    Route::apiResource('transports',    TransportController::class);
    Route::apiResource('student-types', StudentTypeController::class);

    // Subjects
    Route::apiResource('subjects', SubjectController::class);
    Route::get('classes/{classId}/subjects',        [SubjectController::class, 'forClass']);
    Route::post('classes/{classId}/subjects/sync',  [SubjectController::class, 'syncRelations']);

    // Routine structure
    Route::apiResource('periods', RoutinePeriodController::class);
    Route::apiResource('rooms',   RoutineRoomController::class);

    // Class routines
    Route::apiResource('routines', ClassRoutineController::class);
});
