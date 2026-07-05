<?php

namespace App\Modules\Library\Repositories;

use App\Modules\Library\Models\BorrowRecord;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class BorrowRecordRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(BorrowRecord::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'borrow-record';
    }

    /** @return Collection<int, BorrowRecord> */
    public function forSchool(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:all"),
            fn() => BorrowRecord::forSchool($schoolId)->with(['book', 'member.user'])->get(),
        );
    }
}
