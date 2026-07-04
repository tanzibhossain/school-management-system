<?php

namespace Tests\Feature\Website;

use App\Modules\Website\Models\SiteSetting;

class PageTest extends WebsiteTestCase
{
    public function test_admin_can_create_a_page_with_an_auto_generated_slug(): void
    {
        $response = $this->withToken($this->adminToken())
            ->postJson('/api/v2/website/pages', ['title' => 'About Our School'])
            ->assertCreated()
            ->assertJsonFragment(['slug' => 'about-our-school', 'status' => 'draft']);

        $this->assertDatabaseHas('pages', ['school_id' => $this->school->id, 'slug' => 'about-our-school']);
    }

    public function test_duplicate_title_slugs_are_auto_deduplicated(): void
    {
        $this->withToken($this->adminToken())->postJson('/api/v2/website/pages', ['title' => 'Contact'])->assertCreated();

        $second = $this->withToken($this->adminToken())
            ->postJson('/api/v2/website/pages', ['title' => 'Contact'])
            ->assertCreated();

        $this->assertSame('contact-2', $second->json('data.slug'));
    }

    public function test_reserved_slugs_are_never_assigned(): void
    {
        $response = $this->withToken($this->adminToken())
            ->postJson('/api/v2/website/pages', ['title' => 'Admin', 'slug' => 'admin'])
            ->assertCreated();

        $this->assertNotSame('admin', $response->json('data.slug'));
    }

    public function test_changing_the_slug_creates_a_redirect(): void
    {
        $created = $this->withToken($this->adminToken())
            ->postJson('/api/v2/website/pages', ['title' => 'Our Team'])
            ->assertCreated();
        $id = $created->json('data.id');

        $this->withToken($this->adminToken())
            ->putJson("/api/v2/website/pages/{$id}", ['slug' => 'staff-team'])
            ->assertOk()
            ->assertJsonFragment(['slug' => 'staff-team']);

        $this->assertDatabaseHas('page_redirects', [
            'school_id' => $this->school->id,
            'old_slug' => 'our-team',
            'new_slug' => 'staff-team',
        ]);
    }

    public function test_layout_can_be_saved_and_published(): void
    {
        $created = $this->withToken($this->adminToken())
            ->postJson('/api/v2/website/pages', ['title' => 'Home'])
            ->assertCreated();
        $id = $created->json('data.id');

        $layoutResponse = $this->withToken($this->adminToken())
            ->postJson("/api/v2/website/pages/{$id}/layout", ['layout_json' => ['sections' => []]])
            ->assertCreated()
            ->assertJsonFragment(['is_published' => false]);

        $layoutId = $layoutResponse->json('data.id');

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/website/pages/{$id}/publish", ['layout_id' => $layoutId])
            ->assertOk()
            ->assertJsonFragment(['status' => 'published']);

        $this->assertDatabaseHas('page_layouts', ['id' => $layoutId, 'is_published' => 1]);
    }

    public function test_duplicate_creates_a_new_page_with_the_latest_layout(): void
    {
        $created = $this->withToken($this->adminToken())
            ->postJson('/api/v2/website/pages', ['title' => 'Programs'])
            ->assertCreated();
        $id = $created->json('data.id');

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/website/pages/{$id}/layout", ['layout_json' => ['sections' => ['x']]])
            ->assertCreated();

        $duplicate = $this->withToken($this->adminToken())
            ->postJson("/api/v2/website/pages/{$id}/duplicate")
            ->assertCreated();

        $this->assertSame('programs-copy', $duplicate->json('data.slug'));
        $this->assertSame('draft', $duplicate->json('data.status'));
    }

    public function test_revisions_can_be_listed_and_an_old_one_restored_as_a_new_row(): void
    {
        $created = $this->withToken($this->adminToken())
            ->postJson('/api/v2/website/pages', ['title' => 'News'])
            ->assertCreated();
        $id = $created->json('data.id');
        $token = $this->adminToken();

        $first = $this->withToken($token)
            ->postJson("/api/v2/website/pages/{$id}/layout", ['layout_json' => ['v' => 1]])
            ->assertCreated();
        $this->withToken($token)
            ->postJson("/api/v2/website/pages/{$id}/layout", ['layout_json' => ['v' => 2]])
            ->assertCreated();

        $this->withToken($token)->getJson("/api/v2/website/pages/{$id}/revisions")
            ->assertOk()->assertJsonCount(2, 'data');

        $restored = $this->withToken($token)
            ->postJson("/api/v2/website/pages/{$id}/restore/{$first->json('data.id')}")
            ->assertCreated();

        $this->assertSame(['v' => 1], $restored->json('data.layout_json'));
        $this->assertNotSame($first->json('data.id'), $restored->json('data.id'));

        $this->withToken($token)->getJson("/api/v2/website/pages/{$id}/revisions")
            ->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_set_homepage_keeps_page_flag_and_site_setting_in_sync(): void
    {
        $created = $this->withToken($this->adminToken())
            ->postJson('/api/v2/website/pages', ['title' => 'Landing'])
            ->assertCreated();
        $id = $created->json('data.id');

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/website/pages/{$id}/set-homepage")
            ->assertOk()
            ->assertJsonFragment(['is_homepage' => true]);

        $this->assertSame($id, SiteSetting::forSchool($this->school->id)->fresh()->homepage_page_id);
        $this->assertDatabaseHas('pages', ['id' => $id, 'is_homepage' => 1]);
    }

    public function test_teacher_cannot_manage_pages(): void
    {
        $this->withToken($this->teacherToken())
            ->postJson('/api/v2/website/pages', ['title' => 'Nope'])
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v2/website/pages', ['title' => 'Nope'])->assertUnauthorized();
    }
}
