<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Comms (announcements, SMS) + Reports.
 */
class CommsReportsAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
            'sms_cost_per_segment' => 0.5, 'sms_sender_id' => 'SCHOOL',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_open_comms_and_report_screens(): void
    {
        $this->actingAs($this->admin);
        foreach ([
            '/admin/announcements', '/admin/sms',
            '/admin/reports/fee-collection', '/admin/reports/outstanding-dues', '/admin/reports/student-ledger',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    // ── Announcements ────────────────────────────────────────────────────────────

    public function test_create_publish_expire_delete_announcement(): void
    {
        $this->actingAs($this->admin);

        // create + publish immediately
        $this->post('/admin/announcements', [
            'title' => 'Sports Day', 'body' => 'Join us Friday.', 'type' => 'event',
            'audience' => 'all', 'priority' => 'important', 'publish_now' => 1,
        ])->assertRedirect();
        $a = Announcement::where('school_id', $this->school->id)->firstOrFail();
        $this->assertNotNull($a->publish_at);

        // expire
        $this->patch("/admin/announcements/{$a->id}/expire")->assertRedirect();
        $this->assertNotNull($a->fresh()->expire_at);

        // delete (trash)
        $this->delete("/admin/announcements/{$a->id}")->assertRedirect();
        $this->assertDatabaseHas('announcements', ['id' => $a->id, 'is_trash' => true]);
    }

    public function test_create_draft_then_publish(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/announcements', [
            'title' => 'Draft note', 'body' => 'Later.', 'type' => 'general',
            'audience' => 'teachers', 'priority' => 'normal', // no publish_now
        ])->assertRedirect();
        $a = Announcement::where('school_id', $this->school->id)->firstOrFail();
        $this->assertNull($a->publish_at);

        $this->patch("/admin/announcements/{$a->id}/publish")->assertRedirect();
        $this->assertNotNull($a->fresh()->publish_at);
    }

    public function test_announcement_validation(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/announcements', ['title' => '', 'body' => ''])
            ->assertSessionHasErrors(['title', 'body', 'type', 'audience', 'priority']);
    }

    // ── SMS ──────────────────────────────────────────────────────────────────────

    public function test_compose_sms_creates_batch(): void
    {
        $this->actingAs($this->admin);
        Student::create([
            'school_id' => $this->school->id, 'name' => 'Kid', 'gender' => 'male',
            'admission_number' => 'ADM-1', 'status' => 'active',
        ]);

        $this->post('/admin/sms', ['scope' => 'all', 'body' => 'School reopens Monday.'])->assertRedirect();

        $this->assertDatabaseHas('sms_batches', ['school_id' => $this->school->id, 'scope' => 'all', 'message_body' => 'School reopens Monday.']);
    }

    public function test_sms_class_scope_requires_class(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/sms', ['scope' => 'class', 'body' => 'Hi'])->assertSessionHasErrors('class_id');
    }

    // ── Reports ──────────────────────────────────────────────────────────────────

    public function test_fee_collection_report_runs(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/reports/fee-collection?date_from=2026-01-01&date_to=2026-12-31')
            ->assertOk()->assertSee('Payments');
    }

    public function test_outstanding_dues_report_loads_with_summary(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/reports/outstanding-dues')->assertOk()->assertSee('Students With Dues');
    }
}
