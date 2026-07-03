<?php

namespace App\Modules\Leave\Services;

use App\Models\User;
use App\Modules\Academic\Models\Section;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Attendance\Services\WorkingDayService;
use App\Modules\Leave\Models\LeaveType;
use App\Modules\Leave\Models\StudentLeaveRequest;
use App\Modules\Leave\Repositories\StudentLeaveRepository;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentLeaveService
{
    public function __construct(
        private readonly StudentLeaveRepository $repository,
        private readonly WorkingDayService $workingDays,
    ) {}

    /**
     * Submit a leave request. Working days are counted (weekends + holidays
     * excluded) and snapshotted on the row at submission time — later holiday
     * changes never silently change an already-submitted request's day count.
     *
     * @param  array{leave_type_id: int, from_date: string, to_date: string, reason: string, attachment_path?: string|null}  $data
     */
    public function submit(int $schoolId, Student $student, array $data, User $requester): StudentLeaveRequest
    {
        $leaveType = LeaveType::forSchool($schoolId)->active()->applicableTo('student')
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

        $academic = $student->currentAcademic;

        if ($academic === null) {
            throw ValidationException::withMessages([
                'student' => ['Student has no current academic enrollment.'],
            ]);
        }

        $this->assertWithinBalance($schoolId, $student->id, $leaveType, $academic->academic_year_id, $workingDays);

        return StudentLeaveRequest::create([
            'school_id'        => $schoolId,
            'student_id'       => $student->id,
            'class_id'         => $academic->class_id,
            'section_id'       => $academic->section_id,
            'academic_year_id' => $academic->academic_year_id,
            'leave_type_id'    => $leaveType->id,
            'from_date'        => $from->toDateString(),
            'to_date'          => $to->toDateString(),
            'working_days'     => $workingDays,
            'reason'           => $data['reason'],
            'attachment_path'  => $data['attachment_path'] ?? null,
            'requested_by'     => $requester->id,
        ]);
    }

    /**
     * Approve — re-checks the balance under a row lock (two pending requests
     * can both pass the submission-time check but only one may be approved),
     * then pushes attendance status to 'leave' for every working day in range,
     * overriding an existing 'absent' only — never present/late/half_day.
     */
    public function approve(StudentLeaveRequest $request, User $approver): StudentLeaveRequest
    {
        $this->assertCanDecide($request, $approver);

        return DB::transaction(function () use ($request, $approver): StudentLeaveRequest {
            $locked = StudentLeaveRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => ['Only a pending request can be approved.'],
                ]);
            }

            $leaveType = LeaveType::findOrFail($locked->leave_type_id);
            $this->assertWithinBalance(
                $locked->school_id,
                $locked->student_id,
                $leaveType,
                $locked->academic_year_id,
                $locked->working_days,
            );

            $locked->update([
                'status'      => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $this->syncAttendance($locked);

            return $locked->fresh();
        });
    }

    public function reject(StudentLeaveRequest $request, User $approver, ?string $reason = null): StudentLeaveRequest
    {
        $this->assertCanDecide($request, $approver);

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

    /**
     * Cancel — the requester may cancel while still pending; an admin may
     * cancel a pending OR approved request. Cancelling an already-approved
     * request does NOT retroactively revert attendance rows already synced to
     * 'leave' — that requires a normal attendance correction, same as any
     * other manual edit (mirrors the Mark module's "never silently recompute
     * locked results" caution).
     */
    public function cancel(StudentLeaveRequest $request, User $user): StudentLeaveRequest
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

    private function syncAttendance(StudentLeaveRequest $request): void
    {
        $cursor = CarbonImmutable::parse($request->from_date->toDateString());
        $end    = CarbonImmutable::parse($request->to_date->toDateString());

        while ($cursor->lessThanOrEqualTo($end)) {
            if ($this->workingDays->isWorkingDay($request->school_id, $cursor)) {
                $existing = StudentAttendance::forSchool($request->school_id)
                    ->where('student_id', $request->student_id)
                    ->onDate($cursor->toDateString())
                    ->first();

                if ($existing === null) {
                    StudentAttendance::create([
                        'school_id'        => $request->school_id,
                        'student_id'       => $request->student_id,
                        'class_id'         => $request->class_id,
                        'section_id'       => $request->section_id,
                        'academic_year_id' => $request->academic_year_id,
                        'date'             => $cursor->toDateString(),
                        'status'           => 'leave',
                        'recorded_by'      => $request->approved_by,
                    ]);
                } elseif ($existing->status === 'absent') {
                    $existing->update([
                        'status'    => 'leave',
                        'edited_by' => $request->approved_by,
                    ]);
                }
            }

            $cursor = $cursor->addDay();
        }
    }

    private function assertWithinBalance(
        int $schoolId,
        int $studentId,
        LeaveType $leaveType,
        int $academicYearId,
        int $requestedDays,
    ): void {
        if ($leaveType->max_days_per_year === null) {
            return;
        }

        $used = $this->repository->approvedDaysUsed($schoolId, $studentId, $leaveType->id, $academicYearId);

        if ($used + $requestedDays > $leaveType->max_days_per_year) {
            throw ValidationException::withMessages([
                'leave_type_id' => [
                    "This request exceeds the {$leaveType->name} yearly limit of {$leaveType->max_days_per_year} day(s) ({$used} already used).",
                ],
            ]);
        }
    }

    /** Class teacher of the request's section, or admin. */
    private function assertCanDecide(StudentLeaveRequest $request, User $user): void
    {
        if ($user->tokenCan('admin:*')) {
            return;
        }

        $staff = Staff::where('user_id', $user->id)->first();

        $isClassTeacher = $staff !== null
            && $request->section_id !== null
            && Section::whereKey($request->section_id)->where('class_teacher_id', $staff->id)->exists();

        if (! $isClassTeacher) {
            throw new AuthorizationException(
                'Only the section\'s class teacher (or an admin) may decide this request.'
            );
        }
    }
}
