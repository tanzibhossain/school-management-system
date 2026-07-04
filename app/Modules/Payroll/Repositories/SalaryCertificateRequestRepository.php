<?php

namespace App\Modules\Payroll\Repositories;

use App\Modules\Payroll\Models\SalaryCertificateRequest;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/** Status-tracking entity (pending -> generated) — UNCACHED, same caution as PayrollRunRepository. */
class SalaryCertificateRequestRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(SalaryCertificateRequest::class, $cache);
    }

    /** @return Collection<int, SalaryCertificateRequest> */
    public function pendingForSchool(int $schoolId): Collection
    {
        return SalaryCertificateRequest::forSchool($schoolId)
            ->where('status', 'pending')
            ->orderBy('requested_at')
            ->get();
    }

    /** @return Collection<int, SalaryCertificateRequest> */
    public function forStaff(int $schoolId, int $staffId): Collection
    {
        return SalaryCertificateRequest::forSchool($schoolId)
            ->where('staff_id', $staffId)
            ->orderByDesc('requested_at')
            ->get();
    }
}
