<?php

namespace App\Modules\School\Repositories;

use App\Modules\School\Models\ModuleSetting;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class ModuleSettingRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(ModuleSetting::class, $cache);
    }

    /** @return Collection<int, ModuleSetting> */
    public function forSchool(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:all"),
            fn () => ModuleSetting::forSchool($schoolId)->get(),
        );
    }

    public function findForModule(int $schoolId, string $module): ?ModuleSetting
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:module:{$module}"),
            fn () => ModuleSetting::forSchool($schoolId)->where('module', $module)->first(),
        );
    }
}
