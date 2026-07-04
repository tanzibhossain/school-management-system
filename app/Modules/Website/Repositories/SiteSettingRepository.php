<?php

namespace App\Modules\Website\Repositories;

use App\Modules\Website\Models\SiteSetting;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class SiteSettingRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(SiteSetting::class, $cache);
    }

    public function forSchool(int $schoolId): SiteSetting
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}"),
            fn () => SiteSetting::forSchool($schoolId),
        );
    }
}
