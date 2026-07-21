<?php

namespace App\Modules\Examination\Http\Controllers;

use App\Modules\Examination\Http\Requests\AssignSeatingRequest;
use App\Modules\Examination\Http\Resources\ExamSeatingCollection;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamSeating;
use App\Modules\Examination\Services\SeatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RuntimeException;

class ExamSeatingController extends Controller
{
    public function __construct(private readonly SeatingService $seatingService) {}

    /**
     * Full seating chart for an exam — ordered by exam_roll.
     * Loads student name + seat label for admit-card rendering.
     */
    public function show(int $examId): ExamSeatingCollection
    {
        $exam = Exam::where('school_id', app('current_school_id'))->findOrFail($examId);

        $seating = ExamSeating::where('exam_id', $exam->id)
            ->with(['student', 'hallSeat'])
            ->orderBy('exam_roll')
            ->get();

        return new ExamSeatingCollection($seating);
    }

    /**
     * Assign seating for an exam in a given hall.
     *
     * Body: { hall_id: int, strategy?: string }
     *
     * Clears any prior assignment for the exam before re-assigning.
     */
    public function assign(AssignSeatingRequest $request, int $examId): JsonResponse
    {
        $exam = Exam::where('school_id', app('current_school_id'))->findOrFail($examId);

        if ($exam->status === 'completed') {
            return response()->json(['message' => 'Cannot reassign seating for a completed exam.'], 422);
        }

        try {
            $data = $request->validated();
            $strategy = $data['strategy'] ?? $exam->seating_strategy;
            $blankEvery = isset($data['blank_every']) ? (int) $data['blank_every'] : null;
            $count = $this->seatingService->assign($exam, $data['hall_id'], $data['strategy'] ?? null, $blankEvery);

            return response()->json([
                'message' => "{$count} students seated using '{$strategy}' strategy.",
                'students_seated' => $count,
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Clear all seating assignments for an exam.
     */
    public function clear(int $examId): JsonResponse
    {
        $exam = Exam::where('school_id', app('current_school_id'))->findOrFail($examId);

        if ($exam->status === 'completed') {
            return response()->json(['message' => 'Cannot clear seating for a completed exam.'], 422);
        }

        $this->seatingService->clear($exam);

        return response()->json(['message' => 'Seating cleared.']);
    }
}
