<?php

namespace App\Modules\Examination\Repositories;

use App\Modules\Examination\Models\ExamHall;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class ExamHallRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(ExamHall::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'exam_hall';
    }
}
