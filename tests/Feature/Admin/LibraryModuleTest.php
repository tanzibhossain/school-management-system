<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BorrowRecord;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Library optional module (gating, books, members, borrow/return).
 */
class LibraryModuleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    private function enableLibrary(): void
    {
        ModuleSetting::create(['school_id' => $this->school->id, 'module' => 'library', 'is_enabled' => true]);
    }

    public function test_library_is_403_when_module_disabled(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/library/books')->assertForbidden();
    }

    public function test_screens_load_when_enabled(): void
    {
        $this->actingAs($this->admin);
        $this->enableLibrary();

        foreach (['/admin/library/books', '/admin/library/members', '/admin/library/borrow'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_book_crud(): void
    {
        $this->actingAs($this->admin);
        $this->enableLibrary();

        $this->post('/admin/library/books', ['title' => 'Algebra', 'total_copies' => 3])->assertRedirect();
        $book = Book::where('school_id', $this->school->id)->firstOrFail();
        $this->assertEquals(3, $book->available_copies);

        // increasing total copies raises available by the delta
        $this->put("/admin/library/books/{$book->id}", ['title' => 'Algebra', 'total_copies' => 5])->assertRedirect();
        $this->assertEquals(5, $book->fresh()->available_copies);

        $this->patch("/admin/library/books/{$book->id}/deactivate")->assertRedirect();
        $this->assertFalse((bool) $book->fresh()->is_active);
    }

    public function test_member_crud_and_unique_number(): void
    {
        $this->actingAs($this->admin);
        $this->enableLibrary();

        $u1 = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $u2 = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $this->post('/admin/library/members', ['user_id' => $u1->id, 'membership_number' => 'LIB-1', 'member_type' => 'student'])->assertRedirect();
        $this->assertDatabaseHas('library_members', ['school_id' => $this->school->id, 'membership_number' => 'LIB-1']);

        $this->post('/admin/library/members', ['user_id' => $u2->id, 'membership_number' => 'LIB-1', 'member_type' => 'staff'])->assertSessionHasErrors('membership_number');
    }

    public function test_borrow_and_return_flow(): void
    {
        $this->actingAs($this->admin);
        $this->enableLibrary();

        $book = Book::create(['school_id' => $this->school->id, 'title' => 'Physics', 'total_copies' => 2, 'available_copies' => 2, 'is_active' => true]);
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $member = LibraryMember::create(['school_id' => $this->school->id, 'user_id' => $user->id, 'membership_number' => 'LIB-1', 'member_type' => 'student', 'is_active' => true]);

        $this->post('/admin/library/borrow', [
            'book_id' => $book->id, 'library_member_id' => $member->id, 'due_at' => now()->addWeek()->format('Y-m-d'),
        ])->assertRedirect();

        $record = BorrowRecord::where('book_id', $book->id)->firstOrFail();
        $this->assertEquals('borrowed', $record->status);
        $this->assertEquals(1, $book->fresh()->available_copies);

        $this->patch("/admin/library/borrow/{$record->id}/return")->assertRedirect();
        $this->assertNotNull($record->fresh()->returned_at);
        $this->assertEquals(2, $book->fresh()->available_copies);
    }

    public function test_borrow_fails_with_no_available_copies(): void
    {
        $this->actingAs($this->admin);
        $this->enableLibrary();

        $book = Book::create(['school_id' => $this->school->id, 'title' => 'Rare', 'total_copies' => 1, 'available_copies' => 0, 'is_active' => true]);
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $member = LibraryMember::create(['school_id' => $this->school->id, 'user_id' => $user->id, 'membership_number' => 'LIB-2', 'member_type' => 'staff', 'is_active' => true]);

        $this->post('/admin/library/borrow', [
            'book_id' => $book->id, 'library_member_id' => $member->id, 'due_at' => now()->addWeek()->format('Y-m-d'),
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseCount('borrow_records', 0);
    }
}
