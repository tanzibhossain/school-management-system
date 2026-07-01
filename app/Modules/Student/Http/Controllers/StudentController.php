<?php

namespace App\Modules\Student\Http\Controllers;

use App\Modules\Student\Http\Requests\ReAdmitStudentRequest;
use App\Modules\Student\Http\Requests\StoreStudentRequest;
use App\Modules\Student\Http\Requests\TransferStudentRequest;
use App\Modules\Student\Http\Requests\UpdateStudentRequest;
use App\Modules\Student\Http\Resources\StudentListResource;
use App\Modules\Student\Http\Resources\StudentResource;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Repositories\StudentRepository;
use App\Modules\Student\Services\StudentService;
use App\Modules\Student\Services\TransferCertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentService $service,
        private readonly StudentRepository $repository,
        private readonly TransferCertificateService $tcService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $students = $this->repository->paginate($schoolId, $request->only([
            'status', 'class_id', 'section_id', 'academic_year_id', 'search',
        ]));

        return StudentListResource::collection($students);
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $schoolId = app('current_school_id');
        $data     = $request->validated();

        $student = $this->service->enrol(
            schoolId:     $schoolId,
            studentData:  array_intersect_key($data, array_flip([
                'admission_number', 'name', 'dob', 'gender', 'blood_group',
                'religion', 'nationality', 'mother_tongue',
            ])),
            academicData: array_intersect_key($data, array_flip([
                'academic_year_id', 'class_id', 'section_id',
                'version_id', 'group_id', 'shift_id', 'roll_number',
            ])),
            guardianData: $data['guardians'] ?? [],
        );

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $student = $this->service->uploadPhoto($student, $request->file('photo'));
        }

        // Handle sibling linking
        $this->resolveSiblingLink($student, $data, $schoolId);

        return (new StudentResource($student->load([
            'currentAcademic.schoolClass', 'currentAcademic.section',
            'guardians', 'addresses',
        ])))->response()->setStatusCode(201);
    }

    public function show(int $id): StudentResource
    {
        $student = $this->repository->findOrFail($id, app('current_school_id'));

        return new StudentResource($student->load([
            'currentAcademic.schoolClass', 'currentAcademic.section',
            'academics.schoolClass', 'academics.section',
            'guardians', 'addresses', 'documents',
            'siblingLinks.sibling',
        ]));
    }

    public function update(UpdateStudentRequest $request, int $id): StudentResource
    {
        $student = $this->repository->findOrFail($id, app('current_school_id'));

        if ($request->hasFile('photo')) {
            $student = $this->service->uploadPhoto($student, $request->file('photo'));
        }

        $student = $this->service->update($student, $request->except('photo'));

        return new StudentResource($student->load(['currentAcademic.schoolClass', 'guardians']));
    }

    public function transfer(TransferStudentRequest $request, int $id): StudentResource
    {
        $student = $this->repository->findOrFail($id, app('current_school_id'));
        $data    = $request->validated();

        $student = $this->service->transfer($student, $data['reason']);

        // Generate TC draft
        $this->tcService->generate(
            student:    $student,
            reason:     $data['reason'],
            templateId: $data['template_id'] ?? null,
            issuedBy:   $request->user(),
        );

        return new StudentResource($student->fresh());
    }

    public function reAdmit(ReAdmitStudentRequest $request, int $id): StudentResource
    {
        $student = Student::where('school_id', app('current_school_id'))->findOrFail($id);
        $student = $this->service->reAdmit($student, $request->validated());

        return new StudentResource($student->load(['currentAcademic.schoolClass', 'currentAcademic.section']));
    }

    public function destroy(int $id): JsonResponse
    {
        $student = $this->repository->findOrFail($id, app('current_school_id'));
        $this->service->update($student, ['is_trash' => true]);

        return response()->json(['message' => 'Student deleted.']);
    }

    /**
     * Resolve sibling link from admission data (student_id or admission_number).
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveSiblingLink(Student $student, array $data, int $schoolId): void
    {
        $sibling = null;

        if (! empty($data['sibling_student_id'])) {
            $sibling = $this->repository->findByStudentId($data['sibling_student_id'], $schoolId);
        } elseif (! empty($data['sibling_admission_number'])) {
            $sibling = $this->repository->findByAdmissionNumber($data['sibling_admission_number'], $schoolId);
        }

        if ($sibling) {
            $this->service->linkSiblings($student, $sibling);
        }
    }
}
