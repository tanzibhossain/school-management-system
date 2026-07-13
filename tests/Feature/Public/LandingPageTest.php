<?php

namespace Tests\Feature\Public;

use App\Models\User;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Website\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public school homepage at "/" — no auth, renders live notices/stats/staff.
 */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads_without_a_school(): void
    {
        // Fresh install, nothing seeded — must not error.
        $this->get('/')->assertOk();
    }

    public function test_homepage_renders_school_name_and_content(): void
    {
        $school = School::create([
            'name' => 'Greenfield Academy', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
            'address' => '12 School Road', 'email' => 'info@greenfield.test',
        ]);
        SiteSetting::create(['school_id' => $school->id, 'site_name' => 'Greenfield Academy', 'primary_color' => '#0a7d33']);

        $admin = User::factory()->create(['school_id' => $school->id, 'is_active' => true]);
        Announcement::create([
            'school_id' => $school->id, 'created_by' => $admin->id, 'title' => 'Annual Sports Day',
            'body' => 'Join us for the annual sports day this Friday.',
            'audience' => 'all', 'publish_at' => now()->subDay(),
        ]);

        Staff::create([
            'school_id' => $school->id, 'employee_id' => 'EMP-1', 'name' => 'Ayesha Rahman',
            'gender' => 'female', 'status' => 'active', 'joining_date' => now()->subYear(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Greenfield Academy')
            ->assertSee('Annual Sports Day')
            ->assertSee('Ayesha Rahman')
            ->assertSee('Login');
    }

    public function test_homepage_reflects_active_counts(): void
    {
        $school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);

        Staff::create([
            'school_id' => $school->id, 'employee_id' => 'EMP-1', 'name' => 'Teacher One',
            'gender' => 'male', 'status' => 'active', 'joining_date' => now()->subYear(),
        ]);

        $this->get('/')->assertOk()->assertSee('Teachers');
    }
}
