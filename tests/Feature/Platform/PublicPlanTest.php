<?php

namespace Tests\Feature\Platform;

class PublicPlanTest extends PlatformTestCase
{
    public function test_public_plan_list_only_shows_self_serve_plans(): void
    {
        $response = $this->getJson('/api/v2/platform/plans')->assertOk();

        $slugs = collect($response->json('data'))->pluck('slug');

        $this->assertTrue($slugs->contains('trial'));
        $this->assertTrue($slugs->contains('basic'));
        $this->assertTrue($slugs->contains('pro'));
        $this->assertFalse($slugs->contains('demo'), 'Demo is never self-serve purchasable.');
    }

    public function test_no_auth_required(): void
    {
        $this->getJson('/api/v2/platform/plans')->assertOk();
    }
}
