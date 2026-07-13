<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin › Setup › Website appearance — edits the public site's brand colours
 * and top-bar text.
 */
class AppearanceTest extends TestCase
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

    public function test_appearance_page_loads(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/appearance')->assertOk()->assertSee('Top header bar');
    }

    public function test_appearance_update_persists(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/appearance', [
            'site_name' => 'My School', 'primary_color' => '#123456',
            'topbar_welcome' => 'Hello world', 'topbar_text_color' => '#eeeeee',
        ])->assertRedirect();

        $this->assertDatabaseHas('site_settings', [
            'school_id' => $this->school->id, 'site_name' => 'My School',
            'primary_color' => '#123456', 'topbar_welcome' => 'Hello world',
        ]);
    }
}
