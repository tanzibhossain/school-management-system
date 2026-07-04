<?php

namespace App\Modules\Website\Repositories;

use App\Modules\Website\Models\PageTemplate;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class PageTemplateRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(PageTemplate::class, $cache);
    }

    /** Global starter templates + this school's own saved ones. */
    public function availableTo(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:available"),
            fn () => PageTemplate::availableTo($schoolId)->orderBy('name')->get(),
        );
    }
}
