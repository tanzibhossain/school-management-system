<?php

namespace App\Modules\Transport\Repositories;

use App\Modules\Transport\Models\TransportRoute;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class TransportRouteRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(TransportRoute::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'transport-route';
    }

    /** @return Collection<int, TransportRoute> */
    public function allWithRelations(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:all-with-relations"),
            fn () => TransportRoute::forSchool($schoolId)->with(['vehicle', 'driver'])->orderBy('name')->get(),
        );
    }
}
