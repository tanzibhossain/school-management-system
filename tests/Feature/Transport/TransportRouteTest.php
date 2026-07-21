<?php

namespace Tests\Feature\Transport;

use App\Modules\School\Models\ModuleSetting;

class TransportRouteTest extends TransportTestCase
{
    public function test_admin_can_create_route_and_it_creates_a_transport_fee_item(): void
    {
        $res = $this->postJson('/api/v2/transport/routes', [
            'name' => 'North Line',
            'fare' => 25.50,
        ], $this->auth());

        $res->assertStatus(201);
        $res->assertJsonFragment(['name' => 'North Line']);

        $this->assertDatabaseHas('transport_routes', ['name' => 'North Line']);
        // Fee item auto-created for the current year, tied to the route, not mandatory.
        $this->assertDatabaseHas('fee_items', [
            'name' => 'Transport: North Line',
            'amount' => 25.50,
            'is_mandatory' => false,
        ]);
    }

    public function test_fare_change_updates_the_linked_fee_item(): void
    {
        $routeId = $this->createRoute('South Line', 20);

        $this->putJson("/api/v2/transport/routes/{$routeId}", ['fare' => 45], $this->auth())
            ->assertStatus(200)
            ->assertJsonFragment(['fare' => '45.00']);

        $this->assertDatabaseHas('fee_items', ['name' => 'Transport: South Line', 'amount' => 45]);
    }

    public function test_requires_module_enabled(): void
    {
        ModuleSetting::where('school_id', $this->school->id)->where('module', 'transport')->delete();

        $this->getJson('/api/v2/transport/routes', $this->auth())->assertStatus(403);
    }

    public function test_teacher_is_forbidden(): void
    {
        $this->getJson('/api/v2/transport/routes', [
            'Authorization' => 'Bearer '.$this->teacherToken(),
        ])->assertStatus(403);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v2/transport/routes')->assertStatus(401);
    }
}
