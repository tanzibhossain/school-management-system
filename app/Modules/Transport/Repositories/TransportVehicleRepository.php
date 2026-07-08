<?php

namespace App\Modules\Transport\Repositories;

use App\Modules\Transport\Models\TransportVehicle;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class TransportVehicleRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(TransportVehicle::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'transport-vehicle';
    }

    /** @return Collection<int, TransportVehicle> */
    public function available(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:available"),
            fn () => TransportVehicle::forSchool($schoolId)->available()->orderBy('registration_no')->get(),
        );
    }
}
