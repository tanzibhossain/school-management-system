<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_public_homepage_loads(): void
    {
        // "/" is the public school homepage — no auth required.
        $this->get('/')->assertOk();
    }
}
