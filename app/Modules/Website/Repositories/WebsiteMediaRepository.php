<?php

namespace App\Modules\Website\Repositories;

use App\Modules\Website\Models\WebsiteMedia;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class WebsiteMediaRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(WebsiteMedia::class, $cache);
    }

    /** @return Collection<int, WebsiteMedia> */
    public function forSchool(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:all"),
            fn () => WebsiteMedia::forSchool($schoolId)->orderByDesc('created_at')->get(),
        );
    }
}
