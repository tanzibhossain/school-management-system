<?php

namespace Tests\Feature\Website;

class MenuTest extends WebsiteTestCase
{
    public function test_admin_can_create_a_menu_and_replace_its_items_with_one_level_of_nesting(): void
    {
        $token = $this->adminToken();

        $menu = $this->withToken($token)
            ->postJson('/api/v2/website/menus', ['name' => 'Primary Navigation'])
            ->assertCreated();
        $id = $menu->json('data.id');

        $response = $this->withToken($token)
            ->putJson("/api/v2/website/menus/{$id}/items", [
                'items' => [
                    ['label' => 'Home', 'type' => 'page', 'url' => null],
                    [
                        'label' => 'About', 'type' => 'dropdown',
                        'children' => [
                            ['label' => 'Our Team', 'type' => 'external', 'url' => 'https://example.com/team'],
                            ['label' => 'Notice', 'type' => 'dynamic', 'dynamic_route' => '/notice'],
                        ],
                    ],
                ],
            ])
            ->assertOk();

        $this->assertCount(2, $response->json('data.items'));
        $this->assertCount(2, $response->json('data.items.1.children'));
        $this->assertSame('Notice', $response->json('data.items.1.children.1.label'));
    }

    public function test_grandchildren_are_rejected(): void
    {
        $token = $this->adminToken();
        $menu = $this->withToken($token)->postJson('/api/v2/website/menus', ['name' => 'Footer'])->assertCreated();
        $id = $menu->json('data.id');

        $this->withToken($token)
            ->putJson("/api/v2/website/menus/{$id}/items", [
                'items' => [
                    [
                        'label' => 'Parent', 'type' => 'dropdown',
                        'children' => [
                            [
                                'label' => 'Child', 'type' => 'dropdown',
                                'children' => [
                                    ['label' => 'Grandchild', 'type' => 'external', 'url' => 'https://x.com'],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_replacing_items_again_fully_replaces_the_previous_tree(): void
    {
        $token = $this->adminToken();
        $menu = $this->withToken($token)->postJson('/api/v2/website/menus', ['name' => 'Primary'])->assertCreated();
        $id = $menu->json('data.id');

        $this->withToken($token)->putJson("/api/v2/website/menus/{$id}/items", [
            'items' => [['label' => 'One', 'type' => 'external', 'url' => 'https://a.com']],
        ])->assertOk();

        $response = $this->withToken($token)->putJson("/api/v2/website/menus/{$id}/items", [
            'items' => [['label' => 'Two', 'type' => 'external', 'url' => 'https://b.com']],
        ])->assertOk();

        $this->assertCount(1, $response->json('data.items'));
        $this->assertSame('Two', $response->json('data.items.0.label'));
    }

    public function test_teacher_cannot_manage_menus(): void
    {
        $this->withToken($this->teacherToken())
            ->postJson('/api/v2/website/menus', ['name' => 'Nope'])
            ->assertForbidden();
    }
}
