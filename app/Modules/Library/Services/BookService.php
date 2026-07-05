<?php

namespace App\Modules\Library\Services;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Repositories\BookRepository;
use App\Services\BaseService;

class BookService extends BaseService
{
    public function __construct(BookRepository $repository)
    {
        parent::__construct($repository);
    }

    /** @param array<string, mixed> $data */
    public function make(int $schoolId, array $data): Book
    {
        $data['school_id'] = $schoolId;
        $data['available_copies'] = $data['available_copies'] ?? $data['total_copies'];

        $book = Book::create($data);
        $this->repository->flush();

        return $book;
    }

    /** @param array<string, mixed> $data */
    public function modify(Book $book, array $data): Book
    {
        $book->update($data);
        $this->repository->flush();

        return $book->fresh();
    }

    public function deactivate(Book $book): void
    {
        $book->update(['is_active' => false, 'is_trash' => true]);
        $this->repository->flush();
    }
}
