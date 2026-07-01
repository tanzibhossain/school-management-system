<?php

namespace App\Modules\Examination\Http\Controllers;

use App\Modules\Examination\Http\Requests\StoreExamRequest;
use App\Modules\Examination\Http\Requests\UpdateExamRequest;
use App\Modules\Examination\Http\Resources\ExamCollection;
use App\Modules\Examination\Http\Resources\ExamResource;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Repositories\ExaminationRepository;
use App\Modules\Examination\Services\ExaminationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use RuntimeException;

class ExamController extends Controller
{
    public function __construct(
        private readonly ExaminationService $service,
        private readonly ExaminationRepository $repository,
    ) {}

    public function index(): ExamCollection
    {
        $schoolId = app('current_school_id');
        $filters  = request()->only(['class_id', 'academic_year_id', 'status', 'exam_type_id']);

        return new ExamCollection($this->repository->paginate($schoolId, $filters));
    }

    public function show(int $id): ExamResource
    {
        $exam = Exam::where('school_id', app('current_school_id'))
            ->with(['examType', 'subjects.subjectRelation.subject'])
            ->withCount('subjects')
            ->findOrFail($id);

        return new ExamResource($exam);
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        $exam = $this->service->create(app('current_school_id'), $request->validated());

        return (new ExamResource($exam->load('examType')))->response()->setStatusCode(201);
    }

    public function update(UpdateExamRequest $request, int $id): ExamResource|JsonResponse
    {
        $exam = Exam::where('school_id', app('current_school_id'))->findOrFail($id);

        try {
            return new ExamResource($this->service->update($exam, $request->validated()));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $exam = Exam::where('school_id', app('current_school_id'))->findOrFail($id);

        if ($exam->status === 'completed') {
            return response()->json(['message' => 'Cannot delete a completed exam.'], 422);
        }

        $exam->delete();

        return response()->json(['message' => 'Exam deleted.']);
    }

    public function publish(int $id): ExamResource|JsonResponse
    {
        $exam = Exam::where('school_id', app('current_school_id'))->findOrFail($id);

        try {
            return new ExamResource($this->service->publish($exam));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function complete(int $id): ExamResource|JsonResponse
    {
        $exam = Exam::where('school_id', app('current_school_id'))->findOrFail($id);

        try {
            return new ExamResource($this->service->complete($exam));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
