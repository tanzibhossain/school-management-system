<?php

namespace Tests\Feature\Library;

use App\Models\User;
use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\LibraryMember;

class BorrowRecordTest extends LibraryTestCase
{
    public function test_admin_can_borrow_and_return_book()
    {
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        $member = LibraryMember::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'member_type' => 'student',
            'membership_number' => 'LIB-1003',
            'joined_at' => now(),
        ]);

        $book = Book::create([
            'school_id' => $this->school->id,
            'title' => 'The Pragmatic Programmer',
            'author' => 'Andrew Hunt',
            'isbn' => '9780201616224',
            'total_copies' => 2,
            'available_copies' => 2,
        ]);

        $borrowResponse = $this->postJson('/api/v2/library/borrow-records', [
            'library_member_id' => $member->id,
            'book_id' => $book->id,
            'due_at' => now()->addWeek()->toDateTimeString(),
            'notes' => 'First borrow',
        ], [
            'Authorization' => 'Bearer '.$this->adminToken(),
        ]);

        $borrowResponse->assertStatus(201);
        $borrowResponse->assertJsonFragment(['status' => 'borrowed']);
        $this->assertDatabaseHas('borrow_records', [
            'book_id' => $book->id,
            'library_member_id' => $member->id,
            'status' => 'borrowed',
        ]);

        $this->assertDatabaseHas('books', ['id' => $book->id, 'available_copies' => 1]);

        $borrowId = $borrowResponse->json('data.id');

        $returnResponse = $this->postJson("/api/v2/library/borrow-records/{$borrowId}/return", [], [
            'Authorization' => 'Bearer '.$this->adminToken(),
        ]);

        $returnResponse->assertStatus(200);
        $returnResponse->assertJsonFragment(['status' => 'returned']);
        $this->assertDatabaseHas('borrow_records', ['id' => $borrowId, 'status' => 'returned']);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'available_copies' => 2]);
    }
}
