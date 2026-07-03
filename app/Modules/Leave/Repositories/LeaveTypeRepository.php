<?php

namespace App\Modules\Leave\Repositories;

use App\Modules\Leave\Models\LeaveType;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Leave types are low-frequency config data (set up once, rarely edited) —
 * standard cache-aside via BaseRepository, same pattern as FeeItem.
 */
class LeaveTypeRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(LeaveType::class, $cache);
    }

    public function activeFor(int $schoolId, string $person): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:active:{$person}"),
            fn () => LeaveType::forSchool($schoolId)->active()->applicableTo($person)->get(),
        );
    }
}
