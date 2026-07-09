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
        $fetch = fn () => School::with(['phones', 'openingHours'])->first();

        $school = $this->remember($this->cacheKey('current'), $fetch);

        // Self-heal against a stale/incompatible cached entry — an old serialized
        // model can come back from Redis as __PHP_Incomplete_Class, which would
        // otherwise violate the ?School return type and 500. Drop it and re-read.
        if ($school !== null && ! $school instanceof School) {
            $this->flush();

            return $fetch();
        }

        return $school;
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
