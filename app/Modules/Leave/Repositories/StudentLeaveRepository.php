<?php

namespace App\Modules\Leave\Repositories;

use App\Modules\Leave\Models\StudentLeaveRequest;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Leave-request status changes constantly through the approval workflow, so
 * every read here is UNCACHED (same caution as Attendance/Payment/Mark writes).
 */
class StudentLeaveRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(StudentLeaveRequest::class, $cache);
    }

    public function forStudent(int $schoolId, int $studentId): Collection
    {
        return StudentLeaveRequest::forSchool($schoolId)
            ->where('student_id', $studentId)
            ->with('leaveType')
            ->orderByDesc('from_date')
            ->get();
    }

    /** Pending requests awaiting a class teacher's (or admin's) decision. */
    public function pendingForSection(int $schoolId, int $sectionId): Collection
    {
        return StudentLeaveRequest::forSchool($schoolId)
            ->where('section_id', $sectionId)
            ->status('pending')
            ->with(['student:id,name,admission_number', 'leaveType'])
            ->orderBy('from_date')
            ->get();
    }

    /** Approved working days already used against one leave type in one academic year. */
    public function approvedDaysUsed(int $schoolId, int $studentId, int $leaveTypeId, int $academicYearId): int
    {
        return (int) StudentLeaveRequest::forSchool($schoolId)
            ->where('student_id', $studentId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('academic_year_id', $academicYearId)
            ->status('approved')
            ->sum('working_days');
    }
}
