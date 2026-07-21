<?php

namespace App\Modules\Mark\Http\Controllers;

use App\Modules\Examination\Models\Exam;
use App\Modules\Mark\Http\Resources\ExamResultResource;
use App\Modules\Mark\Models\ExamResult;
use App\Modules\Mark\Models\MarkSetting;
use App\Modules\Mark\Services\AnnualResultService;
use App\Modules\Mark\Services\ResultCalculationService;
use App\Modules\Student\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ExamResultController extends Controller
{
    public function __construct(
        private readonly ResultCalculationService $results,
        private readonly AnnualResultService $annual,
    ) {}

    /** POST /v2/marks/results/{examId}/calculate */
    public function calculate(int $examId): JsonResponse
    {
        $written = $this->results->calculateForExam(app('current_school_id'), $examId);

        return response()->json(['data' => ['results_written' => $written]], 201);
    }

    /** POST /v2/marks/results/{examId}/lock — Moderator approval. */
    public function lock(Request $request, int $examId): JsonResponse
    {
        $count = $this->results->lock(app('current_school_id'), $examId, $request->user()->id);

        return response()->json(['data' => ['results_locked' => $count]]);
    }

    /** GET /v2/marks/results/{examId}/tabulation — full class sheet, cached. */
    public function tabulation(Request $request, int $examId): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $results = $this->results->tabulation($schoolId, $examId);

        $request->attributes->set(
            'show_merit',
            $this->showMerit($schoolId, $examId) || $request->user()->tokenCan('admin:*'),
        );

        return ExamResultResource::collection($results);
    }

    /** GET /v2/marks/results/student/{studentId}?exam_id= — own record or admin/teacher. */
    public function studentResult(Request $request, int $studentId): ExamResultResource
    {
        $request->validate(['exam_id' => ['required', 'integer']]);

        $schoolId = app('current_school_id');
        $examId = (int) $request->query('exam_id');

        // Students/parents may only view their own record
        $user = $request->user();
        if (! $user->tokenCan('admin:*') && ! $user->tokenCan('teacher:*')) {
            abort_unless(
                Student::where('school_id', $schoolId)
                    ->whereKey($studentId)
                    ->where('user_id', $user->id)
                    ->exists(),
                403,
                'You may only view your own result.',
            );
        }

        $result = ExamResult::forSchool($schoolId)
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->with('student:id,name,admission_number')
            ->firstOrFail();

        $request->attributes->set(
            'show_merit',
            $this->showMerit($schoolId, $examId) || $request->user()->tokenCan('admin:*'),
        );

        return new ExamResultResource($result);
    }

    /** GET /v2/marks/results/annual?class_id=&academic_year_id= — weighted year-end result. */
    public function annual(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => ['required', 'integer'],
            'academic_year_id' => ['required', 'integer'],
        ]);

        $rows = $this->annual->combined(
            app('current_school_id'),
            (int) $request->query('class_id'),
            (int) $request->query('academic_year_id'),
        );

        return response()->json(['data' => $rows]);
    }

    private function showMerit(int $schoolId, int $examId): bool
    {
        $exam = Exam::where('school_id', $schoolId)->find($examId);

        return $exam !== null
            && MarkSetting::forClass($schoolId, $exam->class_id)->show_merit_position;
    }
}
