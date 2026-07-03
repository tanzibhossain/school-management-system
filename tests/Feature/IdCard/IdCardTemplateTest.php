<?php

namespace Tests\Feature\IdCard;

class IdCardTemplateTest extends IdCardTestCase
{
    public function test_admin_can_create_list_update_and_delete_templates(): void
    {
        $token = $this->adminToken();

        $response = $this->withToken($token)
            ->postJson('/api/v2/id-cards/templates', [
                'type' => 'student',
                'name' => 'Classic Student',
                'layout' => 'horizontal_classic',
                'visible_fields' => ['id', 'class_section'],
                'is_default' => true,
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Classic Student', 'type' => 'student']);

        $id = $response->json('data.id');

        $this->withToken($token)
            ->getJson('/api/v2/id-cards/templates?type=student')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)
            ->putJson("/api/v2/id-cards/templates/{$id}", ['name' => 'Classic Student Updated'])
            ->assertOk()
            ->assertJsonFragment(['name' => 'Classic Student Updated']);

        $this->withToken($token)
            ->deleteJson("/api/v2/id-cards/templates/{$id}")
            ->assertOk();

        $this->assertDatabaseCount('id_card_templates', 0);
    }

    public function test_setting_a_new_default_unsets_the_previous_one_for_that_type(): void
    {
        $token = $this->adminToken();
        $first = $this->studentTemplate(['is_default' => true]);

        $this->withToken($token)
            ->postJson('/api/v2/id-cards/templates', [
                'type' => 'student',
                'name' => 'Second',
                'is_default' => true,
            ])
            ->assertCreated();

        $this->assertFalse($first->fresh()->is_default);
    }

    public function test_non_admin_cannot_create_template(): void
    {
        $this->withToken($this->teacherToken())
            ->postJson('/api/v2/id-cards/templates', ['type' => 'student', 'name' => 'X'])
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v2/id-cards/templates', ['type' => 'student', 'name' => 'X'])
            ->assertUnauthorized();
    }
}
