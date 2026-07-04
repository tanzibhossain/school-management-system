<?php

namespace Tests\Feature\Website;

class SiteSettingTest extends WebsiteTestCase
{
    public function test_show_lazily_creates_the_settings_row(): void
    {
        $this->assertDatabaseMissing('site_settings', ['school_id' => $this->school->id]);

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/website/site-settings')
            ->assertOk()
            ->assertJsonFragment(['global_bg_type' => 'color', 'maintenance_mode' => false]);

        $this->assertDatabaseHas('site_settings', ['school_id' => $this->school->id]);
    }

    public function test_admin_can_update_settings(): void
    {
        $this->withToken($this->adminToken())
            ->putJson('/api/v2/website/site-settings', [
                'primary_color' => '#112233',
                'site_name' => 'Green Academy',
                'maintenance_mode' => true,
            ])
            ->assertOk()
            ->assertJsonFragment(['primary_color' => '#112233', 'site_name' => 'Green Academy', 'maintenance_mode' => true]);
    }

    public function test_teacher_cannot_update_settings(): void
    {
        $this->withToken($this->teacherToken())
            ->putJson('/api/v2/website/site-settings', ['site_name' => 'Nope'])
            ->assertForbidden();
    }
}
