<?php

namespace Tests\Feature\Library;

use App\Modules\Library\Models\Book;

class BookTest extends LibraryTestCase
{
    public function test_admin_can_create_book()
    {
        $response = $this->postJson('/api/v2/library/books', [
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'isbn' => '9780132350884',
            'total_copies' => 3,
        ], [
            'Authorization' => 'Bearer '.$this->adminToken(),
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['title' => 'Clean Code']);
        $this->assertDatabaseHas('books', ['title' => 'Clean Code', 'available_copies' => 3]);
    }

    public function test_admin_can_update_book()
    {
        $book = Book::create([
            'school_id' => $this->school->id,
            'title' => 'Refactoring',
            'author' => 'Martin Fowler',
            'isbn' => '9780201485677',
            'available_copies' => 5,
            'total_copies' => 5,
        ]);

        $response = $this->putJson("/api/v2/library/books/{$book->id}", [
            'title' => 'Refactoring Updated',
            'author' => 'Martin Fowler',
            'isbn' => '9780201485677',
            'total_copies' => 7,
        ], [
            'Authorization' => 'Bearer '.$this->adminToken(),
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Refactoring Updated']);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'total_copies' => 7]);
    }
}
