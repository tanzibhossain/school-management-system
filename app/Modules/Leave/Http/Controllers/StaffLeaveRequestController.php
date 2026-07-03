<?php

namespace App\Modules\Leave\Http\Controllers;

use App\Modules\Leave\Http\Requests\RejectStaffLeaveRequest;
use App\Modules\Leave\Http\Requests\SubmitStaffLeaveRequest;
use App\Modules\Leave\Http\Resources\StaffLeaveRequestResource;
use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Leave\Repositories\StaffLeaveRepository;
use App\Modules\Leave\Services\StaffLeaveService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StaffLeaveRequestController extends Controller
{
    public function __construct(
        private readonly StaffLeaveService $service,
        private readonly StaffLeaveRepository $repository,
    ) {}

    /** POST /v2/leave/staff/{staffId} — submit a leave request, with an optional attachment upload. */
    public function store(SubmitStaffLeaveRequest $request, int $staffId): StaffLeaveRequestResource
    {
        $schoolId = app('current_school_id');
        $staff = Staff::where('school_id', $schoolId)->findOrFail($staffId);

        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store(
                "leave/{$schoolId}/staff", 'minio'
            );
        }

        $leaveRequest = $this->service->submit($schoolId, $staff, $data, $request->user());

        return new StaffLeaveRequestResource($leaveRequest);
    }

    /** GET /v2/leave/staff/{staffId} — one staff member's leave history. */
    public function index(int $staffId): AnonymousResourceCollection
    {
        return StaffLeaveRequestResource::collection(
            $this->repository->forStaff(app('current_school_id'), $staffId)
        );
    }

    /** GET /v2/leave/staff/pending — admin's approval queue. */
    public function pending(): AnonymousResourceCollection
    {
        return StaffLeaveRequestResource::collection(
            $this->repository->pending(app('current_school_id'))
        );
    }

    /** PATCH /v2/leave/staff/{id}/approve */
    public function approve(Request $request, int $id): StaffLeaveRequestResource
    {
        $leaveRequest = StaffLeaveRequest::forSchool(app('current_school_id'))->findOrFail($id);

        return new StaffLeaveRequestResource($this->service->approve($leaveRequest, $request->user()));
    }

    /** PATCH /v2/leave/staff/{id}/reject */
    public function reject(RejectStaffLeaveRequest $request, int $id): StaffLeaveRequestResource
    {
        $leaveRequest = StaffLeaveRequest::forSchool(app('current_school_id'))->findOrFail($id);

        $updated = $this->service->reject($leaveRequest, $request->user(), $request->validated('rejection_reason'));

        return new StaffLeaveRequestResource($updated);
    }

    /** PATCH /v2/leave/staff/{id}/cancel */
    public function cancel(Request $request, int $id): StaffLeaveRequestResource
    {
        $leaveRequest = StaffLeaveRequest::forSchool(app('current_school_id'))->findOrFail($id);

        return new StaffLeaveRequestResource($this->service->cancel($leaveRequest, $request->user()));
    }
}
