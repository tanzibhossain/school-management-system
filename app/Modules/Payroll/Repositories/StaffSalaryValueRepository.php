<?php

namespace App\Modules\Payroll\Repositories;

use App\Modules\Payroll\Models\StaffSalaryValue;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * UNCACHED — these values feed PayrollService::processRun()'s calculation
 * directly inside a financial DB::transaction(); per CLAUDE.md's "no cache on
 * financial write operations" rule, the calculation path always reads live,
 * never through Cache::remember(). (SalaryComponentRepository stays cached —
 * components are lookup/config-like data that changes rarely, same as
 * Designation/Department — the per-staff amounts here are the volatile part.)
 */
class StaffSalaryValueRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(StaffSalaryValue::class, $cache);
    }

    /** @return Collection<int, StaffSalaryValue> */
    public function forStaff(int $schoolId, int $staffId): Collection
    {
        return StaffSalaryValue::forSchool($schoolId)->where('staff_id', $staffId)->get();
    }
}
