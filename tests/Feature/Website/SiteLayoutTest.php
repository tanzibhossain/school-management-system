<?php

namespace Tests\Feature\Website;

class SiteLayoutTest extends WebsiteTestCase
{
    public function test_admin_can_save_and_publish_a_header_layout(): void
    {
        $token = $this->adminToken();

        // PUT here always inserts a NEW versioned row (never updates in place — see
        // CLAUDE.md's Website spec), so Laravel's automatic status-code detection
        // correctly returns 201, not 200.
        $this->withToken($token)
            ->putJson('/api/v2/website/site-layouts/header', ['layout_json' => ['logo' => 'x']])
            ->assertCreated()
            ->assertJsonFragment(['type' => 'header', 'is_published' => false]);

        $this->withToken($token)
            ->postJson('/api/v2/website/site-layouts/header/publish')
            ->assertOk()
            ->assertJsonFragment(['is_published' => true]);
    }

    public function test_invalid_type_is_rejected(): void
    {
        $this->withToken($this->adminToken())
            ->putJson('/api/v2/website/site-layouts/sidebar', ['layout_json' => []])
            ->assertUnprocessable();
    }

    public function test_header_and_footer_are_independent(): void
    {
        $token = $this->adminToken();

        $this->withToken($token)->putJson('/api/v2/website/site-layouts/header', ['layout_json' => ['h' => 1]])->assertCreated();
        $this->withToken($token)->putJson('/api/v2/website/site-layouts/footer', ['layout_json' => ['f' => 1]])->assertCreated();

        $this->withToken($token)->getJson('/api/v2/website/site-layouts/header')
            ->assertOk()->assertJsonFragment(['type' => 'header']);
        $this->withToken($token)->getJson('/api/v2/website/site-layouts/footer')
            ->assertOk()->assertJsonFragment(['type' => 'footer']);
    }
}
