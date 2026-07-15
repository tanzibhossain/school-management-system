<?php

namespace Tests\Feature\Public;

use App\Models\User;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolPhone;
use App\Modules\Website\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public three-row header: top bar (welcome/date/phones), brand bar (logo/name/
 * institution data), nav, and the notice ticker.
 */
class PublicHeaderTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::create([
            'name' => 'Natipota School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
            'institution_code' => '115394', 'institution_code_label' => 'EIIN',
            'school_code' => '5556', 'technical_branch_code' => '0000', 'established' => '1942-01-01',
            'address' => 'Natipota, Damurhuda, Chuadanga',
        ]);
        SiteSetting::create([
            'school_id' => $this->school->id, 'site_name' => 'Natipota School',
            'primary_color' => '#0a6b2f', 'topbar_text_color' => '#ffffff',
        ]);

        // Two numbers flagged to show in the header, one that isn't.
        SchoolPhone::create(['school_id' => $this->school->id, 'phone' => '01309115394', 'show_in_header' => true]);
        SchoolPhone::create(['school_id' => $this->school->id, 'phone' => '01710866871', 'show_in_header' => true]);
        SchoolPhone::create(['school_id' => $this->school->id, 'phone' => '01900000000', 'show_in_header' => false]);
    }

    public function test_header_shows_topbar_phones_and_institution_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('01309115394')                    // header phone 1
            ->assertSee('01710866871')                    // header phone 2
            ->assertSee('tel:01309115394', false)         // clickable
            ->assertDontSee('01900000000')                // not flagged → hidden
            ->assertSee('EIIN:', false)                   // institution label
            ->assertSee('115394')                         // institution code
            ->assertSee('5556')                           // school code
            ->assertSee('1942')                           // established year
            ->assertSee('#0a6b2f', false);                // primary color applied inline
    }

    public function test_ticker_shows_visible_notices(): void
    {
        $admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        Announcement::create([
            'school_id' => $this->school->id, 'created_by' => $admin->id,
            'title' => 'Admission open for class six', 'body' => 'Apply now.',
            'audience' => 'all', 'publish_at' => now()->subDay(),
        ]);

        $this->get('/')->assertOk()
            ->assertSee('data-notice-ticker', false)        // ticker rendered
            ->assertSee('Admission open for class six');
    }

    public function test_ticker_hidden_when_position_is_hidden(): void
    {
        $admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        Announcement::create([
            'school_id' => $this->school->id, 'created_by' => $admin->id,
            'title' => 'Some notice', 'body' => 'Body.', 'audience' => 'all', 'publish_at' => now()->subDay(),
        ]);
        SiteSetting::where('school_id', $this->school->id)->update(['ticker_position' => 'hidden']);

        $this->get('/')->assertOk()->assertDontSee('data-notice-ticker', false);
    }
}
