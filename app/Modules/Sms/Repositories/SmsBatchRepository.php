<?php

namespace App\Modules\Sms\Repositories;

use App\Modules\Sms\Models\SmsBatch;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Batch status moves queued -> processing -> completed/failed while a client
 * polls it, so listing/status reads here are UNCACHED (same caution as
 * IdCard/Loan/Leave's request repositories) — findOrFail (inherited from
 * BaseRepository) is still cache-aside but gets flushed by SmsBatchObserver
 * on every save.
 */
class SmsBatchRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(SmsBatch::class, $cache);
    }

    public function forSchool(int $schoolId): Collection
    {
        return SmsBatch::forSchool($schoolId)
            ->with('logs')
            ->orderByDesc('created_at')
            ->get();
    }
}
