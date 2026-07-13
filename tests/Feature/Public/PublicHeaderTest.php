<?php

namespace Tests\Feature\Public;

use App\Models\User;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\School\Models\School;
use App\Modules\Website\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public three-row header: top bar (welcome/date/phone), brand bar (logo/name/
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
            'topbar_welcome' => 'Welcome to Natipota School', 'topbar_phone' => '01309115394, 01710866871',
            'primary_color' => '#0a6b2f', 'topbar_text_color' => '#ffffff',
        ]);
    }

    public function test_header_shows_topbar_and_institution_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Welcome to Natipota School')   // top bar welcome
            ->assertSee('01309115394, 01710866871')     // top bar phone
            ->assertSee('EIIN:', false)                 // institution label
            ->assertSee('115394')                       // institution code
            ->assertSee('5556')                         // school code
            ->assertSee('1942')                         // established year
            ->assertSee('#0a6b2f', false);              // primary color applied inline
    }

    public function test_ticker_shows_visible_notices(): void
    {
        $admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        Announcement::create([
            'school_id' => $this->school->id, 'created_by' => $admin->id,
            'title' => 'Admission open for class six', 'body' => 'Apply now.',
            'audience' => 'all', 'publish_at' => now()->subDay(),
        ]);

        $this->get('/')->assertOk()->assertSee('Admission open for class six');
    }
}
