<?php

namespace App\Modules\School\Repositories;

use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\School\Models\SchoolPhone;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class SchoolRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(School::class, $cache);
    }

    /**
     * Return the school record with phones and opening hours eager-loaded.
     * Cached forever — flushed by SchoolObserver on any change.
     */
    public function getCurrent(): ?School
    {
        return $this->remember(
            $this->cacheKey('current'),
            fn () => School::with(['phones', 'openingHours'])->first(),
        );
    }

    public function getPhones(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("id:{$schoolId}:phones"),
            fn () => SchoolPhone::where('school_id', $schoolId)->get(),
        );
    }

    public function getOpeningHours(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("id:{$schoolId}:hours"),
            fn () => SchoolOpeningHour::where('school_id', $schoolId)
                ->orderBy('day_of_week')
                ->get(),
        );
    }
}
