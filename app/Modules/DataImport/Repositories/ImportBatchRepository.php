<?php

namespace App\Modules\DataImport\Repositories;

use App\Modules\DataImport\Models\ImportBatch;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Batch status moves queued -> processing -> completed/failed while a client
 * polls it, so listing here is UNCACHED (same caution as IdCard/Sms's batch
 * repositories) — findOrFail (inherited) is still cache-aside but gets
 * flushed by ImportBatchObserver on every save.
 */
class ImportBatchRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(ImportBatch::class, $cache);
    }

    public function forSchool(int $schoolId): Collection
    {
        return ImportBatch::forSchool($schoolId)
            ->orderByDesc('created_at')
            ->get();
    }
}
