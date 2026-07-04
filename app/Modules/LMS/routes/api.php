<?php

use App\Modules\LMS\Http\Controllers\AssignmentController;
use App\Modules\LMS\Http\Controllers\CourseController;
use App\Modules\LMS\Http\Controllers\LessonController;
use App\Modules\LMS\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LMS Module API Routes  —  prefix: /api/v2/lms
|--------------------------------------------------------------------------
| Every route sits behind module.enabled:lms (school_module_settings toggle
| — see app/Http/Middleware/CheckModuleEnabled.php). Reads that both
| students and staff use (course/lesson listing) are gated on the union of
| abilities; ownership beyond that (a teacher only managing courses they
| teach, a student only ever seeing their own submissions) is enforced in
| the controllers, same pattern as Payroll's self-service routes.
*/

Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*,student:*', 'module.enabled:lms'])
    ->prefix('v2/lms')
    ->group(function (): void {
        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{id}/lessons', [CourseController::class, 'lessons'])->whereNumber('id');
        Route::get('/lessons/{id}', [LessonController::class, 'show'])->whereNumber('id');
        Route::get('/courses/{courseId}/assignments', [AssignmentController::class, 'index'])->whereNumber('courseId');
    });

// Course / lesson / assignment management — admin + teacher (ownership of a
// specific course enforced in the controllers, not here).
Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*', 'module.enabled:lms'])
    ->prefix('v2/lms')
    ->group(function (): void {
        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{id}', [CourseController::class, 'update'])->whereNumber('id');
        Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->whereNumber('id');

        Route::post('/courses/{courseId}/lessons', [LessonController::class, 'store'])->whereNumber('courseId');
        Route::put('/lessons/{id}', [LessonController::class, 'update'])->whereNumber('id');
        Route::delete('/lessons/{id}', [LessonController::class, 'destroy'])->whereNumber('id');
        Route::post('/lessons/{id}/publish', [LessonController::class, 'publish'])->whereNumber('id');

        Route::post('/courses/{courseId}/assignments', [AssignmentController::class, 'store'])->whereNumber('courseId');
        Route::put('/assignments/{id}', [AssignmentController::class, 'update'])->whereNumber('id');
        Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy'])->whereNumber('id');
        Route::get('/assignments/{id}/submissions', [AssignmentController::class, 'submissions'])->whereNumber('id');

        Route::post('/submissions/{id}/grade', [SubmissionController::class, 'grade'])->whereNumber('id');
    });

// Submission — student submits their own work; view is shared but ownership
// (a student only ever sees their own) is enforced in the controller.
Route::middleware(['auth:sanctum', 'ability:student:*', 'module.enabled:lms'])
    ->prefix('v2/lms')
    ->group(function (): void {
        Route::post('/assignments/{id}/submit', [SubmissionController::class, 'submit'])->whereNumber('id');
        Route::get('/student/me/submissions', [SubmissionController::class, 'myOwn']);
    });

Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*,student:*', 'module.enabled:lms'])
    ->prefix('v2/lms')
    ->group(function (): void {
        Route::get('/submissions/{id}', [SubmissionController::class, 'show'])->whereNumber('id');
    });
