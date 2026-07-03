<?php

namespace App\Modules\Leave\Repositories;

use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Same caution as StudentLeaveRepository — status changes through the
 * approval workflow, so reads here are UNCACHED.
 */
class StaffLeaveRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(StaffLeaveRequest::class, $cache);
    }

    public function forStaff(int $schoolId, int $staffId): Collection
    {
        return StaffLeaveRequest::forSchool($schoolId)
            ->where('staff_id', $staffId)
            ->with('leaveType')
            ->orderByDesc('from_date')
            ->get();
    }

    public function pending(int $schoolId): Collection
    {
        return StaffLeaveRequest::forSchool($schoolId)
            ->status('pending')
            ->with(['staff:id,name,employee_id', 'leaveType'])
            ->orderBy('from_date')
            ->get();
    }

    /**
     * Approved working days already used against one leave type in a calendar
     * year. Staff have no academic_year_id, so the year is taken from from_date.
     */
    public function approvedDaysUsed(int $schoolId, int $staffId, int $leaveTypeId, int $year): int
    {
        return (int) StaffLeaveRequest::forSchool($schoolId)
            ->where('staff_id', $staffId)
            ->where('leave_type_id', $leaveTypeId)
            ->whereYear('from_date', $year)
            ->status('approved')
            ->sum('working_days');
    }
}
