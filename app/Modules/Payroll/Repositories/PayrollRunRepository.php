<?php

namespace App\Modules\Payroll\Repositories;

use App\Modules\Payroll\Models\PayrollRun;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Financial/status-tracking data (draft -> processed -> approved) — UNCACHED
 * listing, same caution as ImportBatch/AdmissionApplication's repositories.
 * findOrFail (inherited) is still cache-aside but flushed by PayrollRunObserver.
 */
class PayrollRunRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(PayrollRun::class, $cache);
    }

    /** @return Collection<int, PayrollRun> */
    public function forSchool(int $schoolId): Collection
    {
        return PayrollRun::forSchool($schoolId)->orderByDesc('year')->orderByDesc('month')->get();
    }
}
