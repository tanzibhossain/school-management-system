<?php

namespace Tests\Feature\Public;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Website\Models\ContactMessage;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use App\Modules\Website\Models\SiteSetting;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public contact form (contact block) → stored enquiries → admin inbox.
 */
class ContactFormTest extends TestCase
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
        ]);
        SiteSetting::create(['school_id' => $this->school->id, 'site_name' => 'Test School']);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $page = Page::create(['school_id' => $this->school->id, 'slug' => 'contact', 'title' => 'Contact', 'status' => 'published']);
        PageLayout::create([
            'school_id' => $this->school->id, 'page_id' => $page->id, 'is_published' => true, 'published_at' => now(),
            'layout_json' => ['template' => 'full', 'blocks' => [['type' => 'contact', 'data' => ['heading' => 'Get in touch']]]],
        ]);
    }

    public function test_contact_form_renders(): void
    {
        $this->get('/contact')->assertOk()->assertSee('Send message')->assertSee('name="message"', false);
    }

    public function test_submission_stores_an_enquiry(): void
    {
        $this->from('/contact')->post('/contact', [
            'name' => 'Jane Parent', 'email' => 'jane@example.test', 'phone' => '01700000000',
            'subject' => 'Admission query', 'message' => 'Is admission open for class six?',
        ])->assertRedirect('/contact')->assertSessionHas('contact_sent');

        $this->assertDatabaseHas('contact_messages', [
            'school_id' => $this->school->id, 'name' => 'Jane Parent', 'is_read' => false,
        ]);
    }

    public function test_required_fields(): void
    {
        $this->from('/contact')->post('/contact', [])->assertSessionHasErrors(['name', 'message']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_admin_inbox_lists_and_manages_enquiries(): void
    {
        $msg = ContactMessage::create([
            'school_id' => $this->school->id, 'name' => 'Jane Parent',
            'message' => 'Hello there', 'is_read' => false,
        ]);

        $this->actingAs($this->admin);
        $this->get('/admin/enquiries')->assertOk()->assertSee('Jane Parent')->assertSee('Hello there');

        $this->patch("/admin/enquiries/{$msg->id}/read")->assertRedirect();
        $this->assertDatabaseHas('contact_messages', ['id' => $msg->id, 'is_read' => true]);

        $this->delete("/admin/enquiries/{$msg->id}")->assertRedirect();
        $this->assertDatabaseMissing('contact_messages', ['id' => $msg->id]);
    }
}
