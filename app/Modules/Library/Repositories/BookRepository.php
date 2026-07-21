<?php

namespace App\Modules\Library\Repositories;

use App\Modules\Library\Models\Book;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class BookRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Book::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'book';
    }

    /** @return Collection<int, Book> */
    public function available(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:available"),
            fn () => Book::forSchool($schoolId)->available()->get(),
        );
    }
}
