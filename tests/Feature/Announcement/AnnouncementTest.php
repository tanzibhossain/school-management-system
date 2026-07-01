<?php

namespace Tests\Feature\Announcement;

use App\Models\User;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school  = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin   = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $this->admin->assignRole('admin');
        $this->teacher->assignRole('teacher');
    }

    private function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    private function teacherToken(): string
    {
        return $this->teacher->createToken('test', ['*'])->plainTextToken;
    }

    public function test_admin_can_create_draft_announcement(): void
    {
        $response = $this->withToken($this->adminToken())
            ->postJson('/api/v2/announcements', [
                'title'    => 'School Closed Tomorrow',
                'body'     => 'Due to elections, school will remain closed.',
                'type'     => 'holiday',
                'audience' => 'all',
                'priority' => 'important',
            ]);

        $response->assertCreated()->assertJsonFragment(['title' => 'School Closed Tomorrow']);
        $this->assertDatabaseHas('announcements', [
            'title'      => 'School Closed Tomorrow',
            'school_id'  => $this->school->id,
            'publish_at' => null,
        ]);
    }

    public function test_admin_can_publish_announcement(): void
    {
        $ann = Announcement::create([
            'school_id'  => $this->school->id,
            'created_by' => $this->admin->id,
            'title'      => 'Test',
            'body'       => 'Body text.',
            'type'       => 'general',
            'audience'   => 'all',
            'priority'   => 'normal',
        ]);
        auth()->forgetGuards();

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/announcements/{$ann->id}/publish")
            ->assertOk()
            ->assertJsonFragment(['is_published' => true]);

        $this->assertNotNull($ann->fresh()->publish_at);
    }

    public function test_scheduled_announcement_not_visible_before_publish_at(): void
    {
        Announcement::create([
            'school_id'  => $this->school->id,
            'created_by' => $this->admin->id,
            'title'      => 'Future Notice',
            'body'       => 'Not yet.',
            'type'       => 'general',
            'audience'   => 'all',
            'priority'   => 'normal',
            'publish_at' => now()->addDays(2),
        ]);

        $this->withToken($this->teacherToken())
            ->getJson('/api/v2/announcements/feed')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Future Notice']);
    }

    public function test_expired_announcement_not_in_feed(): void
    {
        Announcement::create([
            'school_id'  => $this->school->id,
            'created_by' => $this->admin->id,
            'title'      => 'Old Notice',
            'body'       => 'Expired.',
            'type'       => 'general',
            'audience'   => 'all',
            'priority'   => 'normal',
            'publish_at' => now()->subDays(5),
            'expire_at'  => now()->subDays(1),
        ]);

        $this->withToken($this->teacherToken())
            ->getJson('/api/v2/announcements/feed')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Old Notice']);
    }

    public function test_audience_filtering_teachers_cannot_see_students_only(): void
    {
        Announcement::create([
            'school_id'  => $this->school->id,
            'created_by' => $this->admin->id,
            'title'      => 'Students Only',
            'body'       => 'For students.',
            'type'       => 'exam',
            'audience'   => 'students',
            'priority'   => 'normal',
            'publish_at' => now()->subMinute(),
        ]);

        $this->withToken($this->teacherToken())
            ->getJson('/api/v2/announcements/feed')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Students Only']);
    }

    public function test_pinned_announcement_appears_first(): void
    {
        Announcement::create([
            'school_id'  => $this->school->id,
            'created_by' => $this->admin->id,
            'title'      => 'Regular Notice',
            'body'       => 'Body.',
            'type'       => 'general',
            'audience'   => 'all',
            'priority'   => 'normal',
            'publish_at' => now()->subMinutes(2),
            'is_pinned'  => false,
        ]);

        Announcement::create([
            'school_id'  => $this->school->id,
            'created_by' => $this->admin->id,
            'title'      => 'Pinned Notice',
            'body'       => 'Important.',
            'type'       => 'general',
            'audience'   => 'all',
            'priority'   => 'urgent',
            'publish_at' => now()->subMinute(),
            'is_pinned'  => true,
        ]);

        $response = $this->withToken($this->teacherToken())
            ->getJson('/api/v2/announcements/feed')
            ->assertOk();

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertEquals('Pinned Notice', $titles->first());
    }

    public function test_mark_read_is_idempotent(): void
    {
        $ann = Announcement::create([
            'school_id'  => $this->school->id,
            'created_by' => $this->admin->id,
            'title'      => 'Read Me',
            'body'       => 'Content.',
            'type'       => 'general',
            'audience'   => 'all',
            'priority'   => 'normal',
            'publish_at' => now()->subMinute(),
        ]);

        $token = $this->teacherToken();

        $this->withToken($token)->postJson("/api/v2/announcements/{$ann->id}/read")->assertOk();
        auth()->forgetGuards();
        $this->withToken($token)->postJson("/api/v2/announcements/{$ann->id}/read")->assertOk();

        $this->assertEquals(1, $ann->reads()->where('user_id', $this->teacher->id)->count());
    }

    public function test_admin_list_includes_drafts(): void
    {
        Announcement::create([
            'school_id'  => $this->school->id,
            'created_by' => $this->admin->id,
            'title'      => 'Draft Announcement',
            'body'       => 'Not published yet.',
            'type'       => 'general',
            'audience'   => 'all',
            'priority'   => 'normal',
            // publish_at intentionally null
        ]);

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/announcements')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Draft Announcement']);
    }
}
