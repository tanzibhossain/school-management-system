<?php

namespace App\Modules\Library\Repositories;

use App\Modules\Library\Models\LibraryMember;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class LibraryMemberRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(LibraryMember::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'library-member';
    }
}
