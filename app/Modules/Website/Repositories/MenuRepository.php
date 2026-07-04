<?php

namespace App\Modules\Website\Repositories;

use App\Modules\Website\Models\Menu;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class MenuRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Menu::class, $cache);
    }

    public function forSchool(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:all"),
            fn () => Menu::forSchool($schoolId)->with('items.children')->get(),
        );
    }

    public function withItems(int $schoolId, int $menuId): ?Menu
    {
        return Menu::forSchool($schoolId)->with('items.children')->find($menuId);
    }
}
