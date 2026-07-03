<?php

namespace App\Modules\Leave\Http\Controllers;

use App\Modules\Leave\Http\Requests\RejectStudentLeaveRequest;
use App\Modules\Leave\Http\Requests\SubmitStudentLeaveRequest;
use App\Modules\Leave\Http\Resources\StudentLeaveRequestResource;
use App\Modules\Leave\Models\StudentLeaveRequest;
use App\Modules\Leave\Repositories\StudentLeaveRepository;
use App\Modules\Leave\Services\StudentLeaveService;
use App\Modules\Student\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StudentLeaveRequestController extends Controller
{
    public function __construct(
        private readonly StudentLeaveService $service,
        private readonly StudentLeaveRepository $repository,
    ) {}

    /** POST /v2/leave/students/{studentId} — submit a leave request, with an optional attachment upload. */
    public function store(SubmitStudentLeaveRequest $request, int $studentId): StudentLeaveRequestResource
    {
        $schoolId = app('current_school_id');
        $student = Student::where('school_id', $schoolId)->findOrFail($studentId);

        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store(
                "leave/{$schoolId}/students", 'minio'
            );
        }

        $leaveRequest = $this->service->submit($schoolId, $student, $data, $request->user());

        return new StudentLeaveRequestResource($leaveRequest);
    }

    /** GET /v2/leave/students/{studentId} — one student's leave history. */
    public function index(int $studentId): AnonymousResourceCollection
    {
        return StudentLeaveRequestResource::collection(
            $this->repository->forStudent(app('current_school_id'), $studentId)
        );
    }

    /** GET /v2/leave/students/pending?section_id= — a class teacher's approval queue. */
    public function pendingForSection(Request $request): AnonymousResourceCollection
    {
        $request->validate(['section_id' => ['required', 'integer']]);

        return StudentLeaveRequestResource::collection(
            $this->repository->pendingForSection(app('current_school_id'), (int) $request->query('section_id'))
        );
    }

    /** PATCH /v2/leave/students/{id}/approve */
    public function approve(Request $request, int $id): StudentLeaveRequestResource
    {
        $leaveRequest = StudentLeaveRequest::forSchool(app('current_school_id'))->findOrFail($id);

        return new StudentLeaveRequestResource($this->service->approve($leaveRequest, $request->user()));
    }

    /** PATCH /v2/leave/students/{id}/reject */
    public function reject(RejectStudentLeaveRequest $request, int $id): StudentLeaveRequestResource
    {
        $leaveRequest = StudentLeaveRequest::forSchool(app('current_school_id'))->findOrFail($id);

        $updated = $this->service->reject($leaveRequest, $request->user(), $request->validated('rejection_reason'));

        return new StudentLeaveRequestResource($updated);
    }

    /** PATCH /v2/leave/students/{id}/cancel */
    public function cancel(Request $request, int $id): StudentLeaveRequestResource
    {
        $leaveRequest = StudentLeaveRequest::forSchool(app('current_school_id'))->findOrFail($id);

        return new StudentLeaveRequestResource($this->service->cancel($leaveRequest, $request->user()));
    }
}
