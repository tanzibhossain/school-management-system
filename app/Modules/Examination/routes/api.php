<?php

use App\Modules\Examination\Http\Controllers\ExamController;
use App\Modules\Examination\Http\Controllers\ExamHallController;
use App\Modules\Examination\Http\Controllers\ExamHallSeatController;
use App\Modules\Examination\Http\Controllers\ExamSeatingController;
use App\Modules\Examination\Http\Controllers\ExamSubjectController;
use App\Modules\Examination\Http\Controllers\ExamTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'ability:admin:examination'])
    ->prefix('v2/examination')
    ->group(function (): void {

        // ── Exam types ──────────────────────────────────────────────────────────
        Route::apiResource('exam-types', ExamTypeController::class);

        // ── Exams ───────────────────────────────────────────────────────────────
        Route::apiResource('exams', ExamController::class);
        Route::post('exams/{id}/publish',  [ExamController::class, 'publish']);
        Route::post('exams/{id}/complete', [ExamController::class, 'complete']);

        // ── Exam subjects ───────────────────────────────────────────────────────
        Route::get('exams/{examId}/subjects',          [ExamSubjectController::class, 'index']);
        Route::post('exams/{examId}/subjects',         [ExamSubjectController::class, 'store']);
        Route::delete('exams/{examId}/subjects/{id}',  [ExamSubjectController::class, 'destroy']);

        // ── Exam halls ──────────────────────────────────────────────────────────
        Route::apiResource('exam-halls', ExamHallController::class);
        Route::post('exam-halls/{id}/generate-seats', [ExamHallController::class, 'generateSeats']);
        Route::get('exam-halls/{id}/seats',            [ExamHallController::class, 'seats']);

        // ── Individual seat availability toggle ─────────────────────────────────
        Route::patch(
            'exam-halls/{hallId}/seats/{seatId}/toggle',
            [ExamHallSeatController::class, 'toggle']
        );

        // ── Seating assignment ──────────────────────────────────────────────────
        Route::get('exams/{examId}/seating',    [ExamSeatingController::class, 'show']);
        Route::post('exams/{examId}/seating',   [ExamSeatingController::class, 'assign']);
        Route::delete('exams/{examId}/seating', [ExamSeatingController::class, 'clear']);
    });
