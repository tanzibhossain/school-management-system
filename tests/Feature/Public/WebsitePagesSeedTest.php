<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * After the full seed, every navbar page is published and renders with real
 * (seeded) data — no 404s, no empty shells.
 */
class WebsitePagesSeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // SchoolSeeder + RoleSeeder + AdminSeeder + DemoDataSeeder + WebsitePagesSeeder
    }

    public function test_all_navbar_pages_are_published_and_render(): void
    {
        foreach (['history', 'about', 'mission', 'administration', 'faculty', 'teachers', 'online-admission', 'gallery', 'video', 'contact', 'notices'] as $slug) {
            $this->get("/{$slug}")->assertOk();
        }
    }

    public function test_pages_render_live_seeded_data(): void
    {
        // Faculty page shows a seeded teacher (/staff is the staff portal).
        $this->get('/faculty')->assertOk()->assertSee('Abdul Karim');

        // Notices page shows a seeded announcement.
        $this->get('/notices')->assertOk()->assertSee('Admission open for the new academic year');

        // Contact page pulls the seeded school address (via contact_info sidebar block).
        $this->get('/contact')->assertOk()->assertSee('Natipota, Damurhuda, Chuadanga');

        // Gallery renders the seeded image URLs.
        $this->get('/gallery')->assertOk()->assertSee('picsum.photos', false);

        // Identity pages render their content.
        $this->get('/history')->assertOk()->assertSee('A proud history')->assertSee('Quick links');
        $this->get('/online-admission')->assertOk()->assertSee('Apply for admission');
    }
}
