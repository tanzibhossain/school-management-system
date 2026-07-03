<?php

namespace App\Modules\OnlineAdmission\Repositories;

use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Application status moves submitted -> approved/rejected while staff review
 * the queue, so listing here is UNCACHED (same caution as Leave/Loan's
 * request repositories) — findOrFail (inherited) is still cache-aside but
 * gets flushed by AdmissionApplicationObserver on every save.
 */
class AdmissionApplicationRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(AdmissionApplication::class, $cache);
    }

    public function forSchool(int $schoolId): Collection
    {
        return AdmissionApplication::forSchool($schoolId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function findByReference(int $schoolId, string $referenceNumber): ?AdmissionApplication
    {
        return AdmissionApplication::forSchool($schoolId)
            ->where('reference_number', $referenceNumber)
            ->first();
    }
}
