<?php

namespace App\Modules\Library\Services;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BorrowRecord;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Library\Repositories\BorrowRecordRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BorrowRecordService extends BaseService
{
    public function __construct(BorrowRecordRepository $repository)
    {
        parent::__construct($repository);
    }

    /** @param array<string, mixed> $data */
    public function borrow(int $schoolId, array $data): BorrowRecord
    {
        $member = LibraryMember::forSchool($schoolId)->findOrFail($data['library_member_id']);
        $book = Book::forSchool($schoolId)->findOrFail($data['book_id']);

        if ($book->available_copies < 1) {
            throw new \InvalidArgumentException('No available copies for this book.');
        }

        $borrow = BorrowRecord::create(array_merge($data, [
            'school_id' => $schoolId,
            'status' => 'borrowed',
        ]));

        $book->decrement('available_copies');
        $book->save();

        $this->repository->flush();

        return $borrow->fresh(['book', 'member.user']);
    }

    public function markReturned(int $schoolId, int $id): BorrowRecord
    {
        $borrow = BorrowRecord::forSchool($schoolId)->findOrFail($id);

        if ($borrow->returned_at !== null) {
            return $borrow;
        }

        $borrow->update([
            'returned_at' => now(),
            'status' => $borrow->due_at->isPast() ? 'overdue' : 'returned',
        ]);

        $borrow->book->increment('available_copies');
        $borrow->book->save();

        $this->repository->flush();

        return $borrow->fresh(['book', 'member.user']);
    }
}
