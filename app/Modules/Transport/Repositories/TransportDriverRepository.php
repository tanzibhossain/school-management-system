<?php

namespace App\Modules\Transport\Repositories;

use App\Modules\Transport\Models\TransportDriver;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class TransportDriverRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(TransportDriver::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'transport-driver';
    }
}
