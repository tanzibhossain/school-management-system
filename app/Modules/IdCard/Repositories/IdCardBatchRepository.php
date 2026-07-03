<?php

namespace App\Modules\IdCard\Repositories;

use App\Modules\IdCard\Models\IdCardBatch;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Batch status moves queued -> processing -> completed/failed while a client
 * polls it, so listing/status reads here are UNCACHED (same caution as
 * Loan/Leave's request repositories) — findOrFail (inherited) is still
 * cache-aside but gets flushed by IdCardBatchObserver on every save.
 */
class IdCardBatchRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(IdCardBatch::class, $cache);
    }

    public function forSchool(int $schoolId): Collection
    {
        return IdCardBatch::forSchool($schoolId)
            ->with(['template', 'files'])
            ->orderByDesc('created_at')
            ->get();
    }
}
