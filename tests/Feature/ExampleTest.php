<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_public_homepage_loads(): void
    {
        // "/" is the public school homepage — no auth required. With no school
        // seeded, HomeController renders the empty-state landing (still 200).
        $this->get('/')->assertOk();
    }
}
