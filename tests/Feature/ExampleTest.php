<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_root_redirects_to_the_admin_dashboard(): void
    {
        // Guests hitting "/" are sent to the admin dashboard, which in turn
        // bounces to the login screen.
        $this->get('/')->assertRedirect(route('admin.dashboard'));
    }
}
