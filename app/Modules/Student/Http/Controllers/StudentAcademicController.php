<?php

namespace App\Modules\Student\Http\Controllers;

use App\Modules\Student\Http\Requests\PromoteStudentRequest;
use App\Modules\Student\Http\Resources\StudentAcademicResource;
use App\Modules\Student\Http\Resources\StudentResource;
use App\Modules\Student\Repositories\StudentRepository;
use App\Modules\Student\Services\StudentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StudentAcademicController extends Controller
{
    public function __construct(
        private readonly StudentService $service,
        private readonly StudentRepository $repository,
    ) {}

    /** List all academic records (history) for a student. */
    public function index(int $studentId): AnonymousResourceCollection
    {
        $student = $this->repository->findOrFail($studentId, app('current_school_id'));

        return StudentAcademicResource::collection(
            $student->academics()->with(['year', 'schoolClass', 'section'])->orderByDesc('id')->get()
        );
    }

    /** Promote student to a new class/section. */
    public function promote(PromoteStudentRequest $request, int $studentId): StudentResource
    {
        $student = $this->repository->findOrFail($studentId, app('current_school_id'));
        $data = $request->validated();

        $student = $this->service->promote(
            student: $student,
            toClassId: $data['class_id'],
            toSectionId: $data['section_id'],
            toYearId: $data['academic_year_id'],
            toVersionId: $data['version_id'] ?? null,
            toGroupId: $data['group_id'] ?? null,
            toShiftId: $data['shift_id'] ?? null,
            rollNumber: $data['roll_number'] ?? null,
        );

        return new StudentResource($student->load(['currentAcademic.schoolClass', 'currentAcademic.section']));
    }
}
