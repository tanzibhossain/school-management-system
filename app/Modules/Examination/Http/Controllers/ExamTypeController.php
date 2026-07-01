<?php

namespace App\Modules\Examination\Http\Controllers;

use App\Modules\Examination\Http\Requests\StoreExamTypeRequest;
use App\Modules\Examination\Http\Requests\UpdateExamTypeRequest;
use App\Modules\Examination\Http\Resources\ExamTypeCollection;
use App\Modules\Examination\Http\Resources\ExamTypeResource;
use App\Modules\Examination\Models\ExamType;
use App\Modules\Examination\Services\ExaminationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ExamTypeController extends Controller
{
    public function __construct(private readonly ExaminationService $service) {}

    public function index(): ExamTypeCollection
    {
        $types = ExamType::where('school_id', app('current_school_id'))
            ->orderBy('name')
            ->get();

        return new ExamTypeCollection($types);
    }

    public function store(StoreExamTypeRequest $request): JsonResponse
    {
        $type = $this->service->createType(app('current_school_id'), $request->validated());

        return (new ExamTypeResource($type))->response()->setStatusCode(201);
    }

    public function show(int $id): ExamTypeResource
    {
        $type = ExamType::where('school_id', app('current_school_id'))->findOrFail($id);

        return new ExamTypeResource($type);
    }

    public function update(UpdateExamTypeRequest $request, int $id): ExamTypeResource
    {
        $type = ExamType::where('school_id', app('current_school_id'))->findOrFail($id);

        return new ExamTypeResource($this->service->updateType($type, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $type = ExamType::where('school_id', app('current_school_id'))->findOrFail($id);
        $type->delete();

        return response()->json(['message' => 'Exam type deleted.']);
    }
}
