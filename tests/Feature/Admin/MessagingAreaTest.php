<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Services\ThreadService;
use App\Modules\School\Models\School;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Comms › Messages (admin as staff participant + oversight/lock).
 */
class MessagingAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->teacher->assignRole('teacher');
    }

    public function test_screens_load(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/messages')->assertOk();
        $this->get('/admin/messages/all')->assertOk();
    }

    public function test_compose_creates_thread_and_message(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/messages', [
            'participant_ids' => [$this->teacher->id],
            'body' => 'Hello there',
        ])->assertRedirect();

        $thread = MessageThread::first();
        $this->assertNotNull($thread);
        $this->assertDatabaseHas('messages', ['thread_id' => $thread->id, 'sender_id' => $this->admin->id, 'body' => 'Hello there']);

        $this->get("/admin/messages/{$thread->id}")->assertOk()->assertSee('Hello there');
    }

    public function test_reply_adds_message(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/messages', ['participant_ids' => [$this->teacher->id], 'body' => 'First']);
        $thread = MessageThread::first();

        $this->post("/admin/messages/{$thread->id}/reply", ['body' => 'Second'])->assertRedirect();
        $this->assertDatabaseHas('messages', ['thread_id' => $thread->id, 'body' => 'Second']);
    }

    public function test_lock_blocks_replies(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/messages', ['participant_ids' => [$this->teacher->id], 'body' => 'Hi']);
        $thread = MessageThread::first();

        $this->patch("/admin/messages/{$thread->id}/lock")->assertRedirect();
        $this->assertDatabaseHas('message_threads', ['id' => $thread->id, 'is_locked' => true]);

        $before = Message::where('thread_id', $thread->id)->count();
        $this->post("/admin/messages/{$thread->id}/reply", ['body' => 'Nope'])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertEquals($before, Message::where('thread_id', $thread->id)->count());
    }

    public function test_compose_group_with_subject(): void
    {
        $teacher2 = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $teacher2->assignRole('teacher');

        $this->actingAs($this->admin);
        $this->post('/admin/messages', [
            'participant_ids' => [$this->teacher->id, $teacher2->id],
            'subject' => 'Staff sync',
            'body' => 'Team update',
        ])->assertRedirect();

        $thread = MessageThread::first();
        $this->assertSame('group', $thread->type);
        $this->assertSame('Staff sync', $thread->subject);
        $this->assertEquals(3, $thread->participants()->count()); // admin + 2 teachers
    }

    public function test_oversight_opens_thread_admin_is_not_in(): void
    {
        // A thread between teacher and a student, created directly via the service
        // (the admin UI is admin-only) — the admin is not a participant.
        $student = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $student->assignRole('student');

        $thread = app(ThreadService::class)->create(
            $this->school->id, $this->teacher, [$student->id], null, 'Hi student',
        );

        // Admin sees it via oversight and can open it read-only (not a participant).
        $this->actingAs($this->admin);
        $this->get('/admin/messages/all')->assertOk();
        $this->get("/admin/messages/{$thread->id}")->assertOk()
            ->assertSee('Hi student')
            ->assertSee('Oversight');
    }
}
