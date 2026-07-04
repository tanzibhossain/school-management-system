<?php

namespace Tests\Feature\Website;

class PageTemplateTest extends WebsiteTestCase
{
    public function test_admin_can_save_a_page_as_a_template(): void
    {
        $token = $this->adminToken();

        $page = $this->withToken($token)
            ->postJson('/api/v2/website/pages', ['title' => 'Homepage'])
            ->assertCreated();
        $pageId = $page->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v2/website/pages/{$pageId}/layout", ['layout_json' => ['sections' => ['hero']]])
            ->assertCreated();

        $template = $this->withToken($token)
            ->postJson('/api/v2/website/page-templates', ['page_id' => $pageId, 'name' => 'School Homepage'])
            ->assertCreated();

        $this->assertSame(['sections' => ['hero']], $template->json('data.layout_json'));

        $this->withToken($token)->getJson('/api/v2/website/page-templates')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_global_templates_are_included_alongside_school_owned_ones(): void
    {
        \App\Modules\Website\Models\PageTemplate::create([
            'school_id' => null,
            'name' => 'Blank',
            'layout_json' => ['sections' => []],
        ]);

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/website/page-templates')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Blank']);
    }
}
