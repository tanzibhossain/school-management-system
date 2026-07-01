<?php

namespace App\Modules\Examination\Http\Controllers;

use App\Modules\Examination\Http\Requests\StoreExamSubjectRequest;
use App\Modules\Examination\Http\Resources\ExamSubjectResource;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Examination\Services\ExaminationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RuntimeException;

class ExamSubjectController extends Controller
{
    public function __construct(private readonly ExaminationService $service) {}

    public function index(int $examId): JsonResponse
    {
        $exam     = Exam::where('school_id', app('current_school_id'))->findOrFail($examId);
        $subjects = $exam->subjects()->with('subjectRelation.subject')->get();

        return response()->json([
            'data' => ExamSubjectResource::collection($subjects),
        ]);
    }

    public function store(StoreExamSubjectRequest $request, int $examId): JsonResponse
    {
        $exam = Exam::where('school_id', app('current_school_id'))->findOrFail($examId);

        try {
            $subject = $this->service->addSubject($exam, $request->validated());

            return (new ExamSubjectResource($subject->load('subjectRelation.subject')))
                ->response()->setStatusCode(201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $examId, int $id): JsonResponse
    {
        $exam    = Exam::where('school_id', app('current_school_id'))->findOrFail($examId);
        $subject = ExamSubject::where('exam_id', $exam->id)->findOrFail($id);

        try {
            $this->service->removeSubject($subject);

            return response()->json(['message' => 'Subject removed from exam.']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
