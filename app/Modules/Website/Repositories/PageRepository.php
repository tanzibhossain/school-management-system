<?php

namespace App\Modules\Website\Repositories;

use App\Modules\Website\Models\Page;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Page status/publish state changes frequently while an editor is working, so
 * listing here is UNCACHED (same caution as every other batch/request-style
 * repository in this codebase) — findOrFail (inherited) is still cache-aside
 * but gets flushed by PageObserver on every save.
 */
class PageRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Page::class, $cache);
    }

    public function forSchool(int $schoolId): Collection
    {
        return Page::forSchool($schoolId)->orderByDesc('updated_at')->get();
    }

    public function findBySlug(int $schoolId, string $slug): ?Page
    {
        return Page::forSchool($schoolId)->where('slug', $slug)->first();
    }
}
