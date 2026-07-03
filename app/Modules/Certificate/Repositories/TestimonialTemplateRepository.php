<?php

namespace App\Modules\Certificate\Repositories;

use App\Modules\Certificate\Models\TestimonialTemplate;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class TestimonialTemplateRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(TestimonialTemplate::class, $cache);
    }

    public function default(int $schoolId): ?TestimonialTemplate
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:default"),
            fn () => TestimonialTemplate::forSchool($schoolId)->default()->first(),
        );
    }
}
