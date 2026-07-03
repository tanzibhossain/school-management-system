<?php

namespace App\Modules\IdCard\Repositories;

use App\Modules\IdCard\Models\IdCardTemplate;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class IdCardTemplateRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(IdCardTemplate::class, $cache);
    }

    public function ofType(int $schoolId, string $type): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:type:{$type}"),
            fn () => IdCardTemplate::forSchool($schoolId)->ofType($type)->get(),
        );
    }

    public function defaultForType(int $schoolId, string $type): ?IdCardTemplate
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:type:{$type}:default"),
            fn () => IdCardTemplate::forSchool($schoolId)->ofType($type)->default()->first(),
        );
    }
}
