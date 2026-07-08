<?php

namespace App\Modules\Library\Services;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BorrowRecord;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Library\Repositories\BorrowRecordRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class BorrowRecordService extends BaseService
{
    public function __construct(BorrowRecordRepository $repository)
    {
        parent::__construct($repository);
    }

    /** @param array<string, mixed> $data */
    public function borrow(int $schoolId, array $data): BorrowRecord
    {
        // Transaction + lockForUpdate: without them two concurrent borrows of the
        // last copy both pass the availability check and both decrement, overselling
        // the book (and driving the unsignedInteger column below zero -> DB error).
        // Same pattern Payment/CreditService uses for balance mutations.
        $borrow = DB::transaction(function () use ($schoolId, $data): BorrowRecord {
            LibraryMember::forSchool($schoolId)->findOrFail($data['library_member_id']);
            $book = Book::forSchool($schoolId)->lockForUpdate()->findOrFail($data['book_id']);

            if ($book->available_copies < 1) {
                throw new \InvalidArgumentException('No available copies for this book.');
            }

            $book->decrement('available_copies');

            return BorrowRecord::create(array_merge($data, [
                'school_id' => $schoolId,
                'status' => 'borrowed',
            ]));
        });

        $this->repository->flush();

        return $borrow->fresh(['book', 'member.user']);
    }

    public function markReturned(int $schoolId, int $id): BorrowRecord
    {
        $borrow = DB::transaction(function () use ($schoolId, $id): BorrowRecord {
            $borrow = BorrowRecord::forSchool($schoolId)->lockForUpdate()->findOrFail($id);

            if ($borrow->returned_at !== null) {
                return $borrow;
            }

            // Always 'returned' once handed back — a late return is still returned.
            // "Overdue" is a property of an OUTSTANDING loan (due_at past AND
            // returned_at null), derived at read time, never a terminal status.
            $borrow->update([
                'returned_at' => now(),
                'status' => 'returned',
            ]);

            Book::forSchool($schoolId)->lockForUpdate()->findOrFail($borrow->book_id)
                ->increment('available_copies');

            return $borrow;
        });

        $this->repository->flush();

        return $borrow->fresh(['book', 'member.user']);
    }
}
