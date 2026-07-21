<?php

namespace Tests\Feature\Transport;

use App\Modules\Transport\Models\StudentTransportAssignment;

class AssignmentTest extends TransportTestCase
{
    public function test_seat_capacity_is_enforced(): void
    {
        [$routeId] = $this->routeWithVehicle(capacity: 1);

        $a = $this->makeStudent();
        $b = $this->makeStudent();

        $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $routeId, 'student_id' => $a->id,
        ], $this->auth())->assertStatus(201);

        $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $routeId, 'student_id' => $b->id,
        ], $this->auth())->assertStatus(422);
    }

    public function test_a_route_without_a_vehicle_blocks_new_assignments(): void
    {
        $routeId = $this->createRoute('No Bus Line', 10); // no vehicle attached

        $student = $this->makeStudent();
        $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $routeId, 'student_id' => $student->id,
        ], $this->auth())->assertStatus(422);
    }

    public function test_a_student_can_have_only_one_active_assignment(): void
    {
        [$route1] = $this->routeWithVehicle(capacity: 5);
        [$route2] = $this->routeWithVehicle(capacity: 5);
        $student = $this->makeStudent();

        $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $route1, 'student_id' => $student->id,
        ], $this->auth())->assertStatus(201);

        $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $route2, 'student_id' => $student->id,
        ], $this->auth())->assertStatus(422);
    }

    public function test_ending_an_assignment_frees_the_student(): void
    {
        [$routeId] = $this->routeWithVehicle(capacity: 5);
        $student = $this->makeStudent();

        $create = $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $routeId, 'student_id' => $student->id,
        ], $this->auth())->assertStatus(201);
        $id = $create->json('data.id');

        $this->patchJson("/api/v2/transport/assignments/{$id}/end", [], $this->auth())
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'ended']);

        // Freed — can now be reassigned.
        [$route2] = $this->routeWithVehicle(capacity: 5);
        $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $route2, 'student_id' => $student->id,
        ], $this->auth())->assertStatus(201);
    }

    public function test_overdue_is_derived_not_stored(): void
    {
        [$routeId] = $this->routeWithVehicle(capacity: 5);
        $student = $this->makeStudent();

        // Active assignment whose end date is already in the past.
        StudentTransportAssignment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'transport_route_id' => $routeId,
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        $res = $this->getJson('/api/v2/transport/assignments?status=active', $this->auth());
        $res->assertStatus(200);
        $res->assertJsonFragment(['is_expired' => true]);

        // Still stored as 'active', not a bogus 'expired' status.
        $this->assertDatabaseHas('student_transport_assignments', [
            'student_id' => $student->id, 'status' => 'active',
        ]);
    }

    public function test_roster_lists_active_riders(): void
    {
        [$routeId] = $this->routeWithVehicle(capacity: 5);
        $student = $this->makeStudent();
        $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $routeId, 'student_id' => $student->id,
        ], $this->auth())->assertStatus(201);

        $this->getJson("/api/v2/transport/routes/{$routeId}/roster", $this->auth())
            ->assertStatus(200)
            ->assertJsonFragment(['student_id' => $student->id]);
    }
}
