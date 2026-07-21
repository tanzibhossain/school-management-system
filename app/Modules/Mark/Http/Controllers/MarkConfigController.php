<?php

namespace App\Modules\Mark\Http\Controllers;

use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Mark\Http\Requests\ApplyTemplateRequest;
use App\Modules\Mark\Http\Requests\StoreMarkDivisionRequest;
use App\Modules\Mark\Http\Requests\UpdateMarkSettingsRequest;
use App\Modules\Mark\Http\Resources\GradeBoundaryResource;
use App\Modules\Mark\Http\Resources\MarkDivisionResource;
use App\Modules\Mark\Http\Resources\MarkSettingResource;
use App\Modules\Mark\Models\GradeBoundary;
use App\Modules\Mark\Models\MarkDivision;
use App\Modules\Mark\Models\MarkSetting;
use App\Modules\Mark\Services\GradeTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class MarkConfigController extends Controller
{
    public function __construct(
        private readonly GradeTemplateService $templates,
    ) {}

    /** GET /v2/marks/settings/{classId} — force 200 (row may be lazily created). */
    public function showSettings(int $classId): JsonResponse
    {
        return (new MarkSettingResource(
            MarkSetting::forClass(app('current_school_id'), $classId)
        ))->response()->setStatusCode(200);
    }

    /** PUT /v2/marks/settings/{classId} */
    public function updateSettings(UpdateMarkSettingsRequest $request, int $classId): JsonResponse
    {
        $settings = MarkSetting::forClass(app('current_school_id'), $classId);
        $settings->update($request->validated());

        return (new MarkSettingResource($settings->fresh()))->response()->setStatusCode(200);
    }

    /** GET /v2/marks/grade-boundaries/{classId} */
    public function boundaries(int $classId): AnonymousResourceCollection
    {
        return GradeBoundaryResource::collection(
            GradeBoundary::forClass(app('current_school_id'), $classId)
                ->orderByDesc('min_percent')->get()
        );
    }

    /** POST /v2/marks/grade-boundaries/{classId}/apply-template */
    public function applyGradeTemplate(ApplyTemplateRequest $request, int $classId): JsonResponse
    {
        $count = $this->templates->applyGradeTemplate(
            app('current_school_id'),
            $classId,
            $request->validated('template'),
        );

        return response()->json(['data' => ['boundaries_created' => $count]], 201);
    }

    /** POST /v2/marks/divisions — create one division manually. */
    public function storeDivision(StoreMarkDivisionRequest $request): MarkDivisionResource
    {
        $schoolId = app('current_school_id');
        $examSubject = ExamSubject::where('school_id', $schoolId)
            ->findOrFail((int) $request->validated('exam_subject_id'));

        $division = MarkDivision::create($request->validated() + [
            'school_id' => $schoolId,
            'exam_id' => $examSubject->exam_id,
        ]);

        return new MarkDivisionResource($division);
    }

    /** GET /v2/marks/divisions?exam_subject_id= */
    public function divisions(int $examSubjectId): AnonymousResourceCollection
    {
        return MarkDivisionResource::collection(
            MarkDivision::forSchool(app('current_school_id'))
                ->where('exam_subject_id', $examSubjectId)
                ->orderBy('display_order')->get()
        );
    }

    /** POST /v2/marks/divisions/{examSubjectId}/apply-template */
    public function applyDivisionTemplate(ApplyTemplateRequest $request, int $examSubjectId): JsonResponse
    {
        $count = $this->templates->applyDivisionTemplate(
            app('current_school_id'),
            $examSubjectId,
            $request->validated('template'),
        );

        return response()->json(['data' => ['divisions_created' => $count]], 201);
    }
}
