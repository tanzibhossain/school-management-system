<?php

use App\Modules\Mark\Http\Controllers\ExamResultController;
use App\Modules\Mark\Http\Controllers\ExamWeightController;
use App\Modules\Mark\Http\Controllers\MarkConfigController;
use App\Modules\Mark\Http\Controllers\MarkEntryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mark Module API Routes  —  prefix: /api/v2/marks
|--------------------------------------------------------------------------
*/

// ── Teachers + admins: mark entry and viewing ────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*'])->prefix('v2/marks')->group(function (): void {
    Route::post('/enter', [MarkEntryController::class, 'bulkStore']);
    Route::get('/divisions/{divisionId}/marks', [MarkEntryController::class, 'forDivision'])->whereNumber('divisionId');
    Route::get('/results/{examId}/tabulation', [ExamResultController::class, 'tabulation'])->whereNumber('examId');
});

// ── Students/parents + staff: own result ─────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*,student:*,parent:*'])->prefix('v2/marks')->group(function (): void {
    Route::get('/results/student/{studentId}', [ExamResultController::class, 'studentResult'])->whereNumber('studentId');
});

// ── Admin only: configuration, calculation, locking ──────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/marks')->group(function (): void {
    Route::get('/settings/{classId}', [MarkConfigController::class, 'showSettings'])->whereNumber('classId');
    Route::put('/settings/{classId}', [MarkConfigController::class, 'updateSettings'])->whereNumber('classId');

    Route::get('/grade-boundaries/{classId}', [MarkConfigController::class, 'boundaries'])->whereNumber('classId');
    Route::post('/grade-boundaries/{classId}/apply-template', [MarkConfigController::class, 'applyGradeTemplate'])->whereNumber('classId');

    Route::post('/divisions', [MarkConfigController::class, 'storeDivision']);
    Route::get('/divisions/subject/{examSubjectId}', [MarkConfigController::class, 'divisions'])->whereNumber('examSubjectId');
    Route::post('/divisions/{examSubjectId}/apply-template', [MarkConfigController::class, 'applyDivisionTemplate'])->whereNumber('examSubjectId');

    Route::post('/{markId}/grace', [MarkEntryController::class, 'applyGrace'])->whereNumber('markId');

    Route::post('/results/{examId}/calculate', [ExamResultController::class, 'calculate'])->whereNumber('examId');
    Route::post('/results/{examId}/lock', [ExamResultController::class, 'lock'])->whereNumber('examId');
    Route::get('/results/annual', [ExamResultController::class, 'annual']);

    Route::put('/exam-weights', [ExamWeightController::class, 'upsert']);
    Route::get('/exam-weights', [ExamWeightController::class, 'index']);

    Route::put('/student-subjects', [ExamWeightController::class, 'enrollStudent']);
});
