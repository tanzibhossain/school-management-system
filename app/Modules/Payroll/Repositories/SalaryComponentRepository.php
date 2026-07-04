<?php

namespace App\Modules\Payroll\Repositories;

use App\Modules\Payroll\Models\SalaryComponent;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class SalaryComponentRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(SalaryComponent::class, $cache);
    }

    /** @return Collection<int, SalaryComponent> */
    public function forSchool(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:active"),
            fn () => SalaryComponent::forSchool($schoolId)->active()->orderBy('sort_order')->get(),
        );
    }
}
