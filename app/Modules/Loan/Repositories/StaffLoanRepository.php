<?php

namespace App\Modules\Loan\Repositories;

use App\Modules\Loan\Models\StaffLoan;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Loan status changes through the approval workflow, so reads here are
 * UNCACHED (same caution as Leave's request repositories).
 */
class StaffLoanRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(StaffLoan::class, $cache);
    }

    public function forStaff(int $schoolId, int $staffId): Collection
    {
        return StaffLoan::forSchool($schoolId)
            ->where('staff_id', $staffId)
            ->with('schedules')
            ->orderByDesc('start_date')
            ->get();
    }

    /** Pending requests awaiting an admin/accountant decision. */
    public function pending(int $schoolId): Collection
    {
        return StaffLoan::forSchool($schoolId)
            ->status('pending')
            ->with('staff:id,name,employee_id')
            ->orderBy('created_at')
            ->get();
    }
}
