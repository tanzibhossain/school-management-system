<?php

namespace App\Modules\Leave\Services;

use App\Models\User;
use App\Modules\Attendance\Services\WorkingDayService;
use App\Modules\Leave\Models\LeaveType;
use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Leave\Repositories\StaffLeaveRepository;
use App\Modules\Staff\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staff leave has no attendance-integration requirement (StaffAttendance is
 * punch-based check_in/check_out, not a daily status enum like student
 * attendance) — CLAUDE.md only spec's the student-side sync. Approval is
 * admin-only: the Staff model has no manager/line-supervisor field to reuse
 * the class-teacher-style delegation used for student leave.
 */
class StaffLeaveService
{
    public function __construct(
        private readonly StaffLeaveRepository $repository,
        private readonly WorkingDayService $workingDays,
    ) {}

    /**
     * @param  array{leave_type_id: int, from_date: string, to_date: string, reason: string, attachment_path?: string|null}  $data
     */
    public function submit(int $schoolId, Staff $staff, array $data, User $requester): StaffLeaveRequest
    {
        $leaveType = LeaveType::forSchool($schoolId)->active()->applicableTo('staff')
            ->findOrFail($data['leave_type_id']);

        $from = CarbonImmutable::parse($data['from_date']);
        $to   = CarbonImmutable::parse($data['to_date']);

        if ($to->lessThan($from)) {
            throw ValidationException::withMessages([
                'to_date' => ['to_date must be on or after from_date.'],
            ]);
        }

        if ($leaveType->requires_attachment && empty($data['attachment_path'])) {
            throw ValidationException::withMessages([
                'attachment' => ["{$leaveType->name} requires an attachment."],
            ]);
        }

        $workingDays = $this->workingDays->countWorkingDays($schoolId, $from, $to);

        if ($workingDays === 0) {
            throw ValidationException::withMessages([
                'from_date' => ['The selected range contains no working days.'],
            ]);
        }

        $this->assertWithinBalance($schoolId, $staff->id, $leaveType, $from->year, $workingDays);

        return StaffLeaveRequest::create([
            'school_id'       => $schoolId,
            'staff_id'        => $staff->id,
            'leave_type_id'   => $leaveType->id,
            'from_date'       => $from->toDateString(),
            'to_date'         => $to->toDateString(),
            'working_days'    => $workingDays,
            'reason'          => $data['reason'],
            'attachment_path' => $data['attachment_path'] ?? null,
            'requested_by'    => $requester->id,
        ]);
    }

    public function approve(StaffLeaveRequest $request, User $approver): StaffLeaveRequest
    {
        $this->assertIsAdmin($approver);

        return DB::transaction(function () use ($request, $approver): StaffLeaveRequest {
            $locked = StaffLeaveRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => ['Only a pending request can be approved.'],
                ]);
            }

            $leaveType = LeaveType::findOrFail($locked->leave_type_id);
            $this->assertWithinBalance(
                $locked->school_id,
                $locked->staff_id,
                $leaveType,
                CarbonImmutable::parse($locked->from_date->toDateString())->year,
                $locked->working_days,
            );

            $locked->update([
                'status'      => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    public function reject(StaffLeaveRequest $request, User $approver, ?string $reason = null): StaffLeaveRequest
    {
        $this->assertIsAdmin($approver);

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Only a pending request can be rejected.'],
            ]);
        }

        $request->update([
            'status'           => 'rejected',
            'approved_by'      => $approver->id,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        return $request->fresh();
    }

    /** Requester may cancel while pending; admin may cancel pending or approved. */
    public function cancel(StaffLeaveRequest $request, User $user): StaffLeaveRequest
    {
        $isAdmin = $user->tokenCan('admin:*');

        if (! $isAdmin && ($request->requested_by !== $user->id || $request->status !== 'pending')) {
            throw new AuthorizationException(
                'Only the requester (while pending) or an admin may cancel this request.'
            );
        }

        if (! in_array($request->status, ['pending', 'approved'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Only a pending or approved request can be cancelled.'],
            ]);
        }

        $request->update(['status' => 'cancelled']);

        return $request->fresh();
    }

    private function assertWithinBalance(
        int $schoolId,
        int $staffId,
        LeaveType $leaveType,
        int $year,
        int $requestedDays,
    ): void {
        if ($leaveType->max_days_per_year === null) {
            return;
        }

        $used = $this->repository->approvedDaysUsed($schoolId, $staffId, $leaveType->id, $year);

        if ($used + $requestedDays > $leaveType->max_days_per_year) {
            throw ValidationException::withMessages([
                'leave_type_id' => [
                    "This request exceeds the {$leaveType->name} yearly limit of {$leaveType->max_days_per_year} day(s) ({$used} already used).",
                ],
            ]);
        }
    }

    private function assertIsAdmin(User $user): void
    {
        if (! $user->tokenCan('admin:*')) {
            throw new AuthorizationException('Only an admin may decide staff leave requests.');
        }
    }
}
