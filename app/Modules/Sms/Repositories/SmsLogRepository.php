<?php

namespace App\Modules\Sms\Repositories;

use App\Modules\Sms\Models\SmsLog;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class SmsLogRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(SmsLog::class, $cache);
    }

    public function forBatch(int $schoolId, int $batchId): Collection
    {
        return SmsLog::forSchool($schoolId)
            ->where('batch_id', $batchId)
            ->orderBy('id')
            ->get();
    }

    public function failed(int $schoolId): Collection
    {
        return SmsLog::forSchool($schoolId)
            ->status('failed')
            ->orderByDesc('created_at')
            ->get();
    }
}
