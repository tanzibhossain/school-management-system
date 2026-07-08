<?php

namespace Tests\Feature\Transport;

use App\Modules\Transport\Models\TransportDriver;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\TransportVehicle;

class VehicleSwapTest extends TransportTestCase
{
    public function test_swap_promotes_pool_vehicle_demotes_old_keeps_driver_and_notifies_riders(): void
    {
        [$routeId, $oldVehicleId] = $this->routeWithVehicle(capacity: 40);

        // Give the route a driver — it must survive the swap.
        $driver = TransportDriver::create(['school_id' => $this->school->id, 'name' => 'Mr. Karim', 'status' => 'active']);
        $this->putJson("/api/v2/transport/routes/{$routeId}", ['driver_id' => $driver->id], $this->auth())->assertStatus(200);

        // One rider on the route.
        $student = $this->makeStudent();
        $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $routeId,
            'student_id' => $student->id,
        ], $this->auth())->assertStatus(201);

        $replacement = $this->makeVehicle(capacity: 40);

        $res = $this->postJson("/api/v2/transport/routes/{$routeId}/swap-vehicle", [
            'replacement_vehicle_id' => $replacement->id,
        ], $this->auth());

        $res->assertStatus(200);
        $res->assertJsonFragment(['current_vehicle_id' => $replacement->id]);
        $res->assertJsonFragment(['driver_id' => $driver->id]); // driver unchanged

        $this->assertDatabaseHas('transport_vehicles', ['id' => $oldVehicleId, 'status' => 'out_of_service']);
        $this->assertDatabaseHas('transport_vehicles', ['id' => $replacement->id, 'status' => 'in_service']);
        $this->assertEquals($driver->id, TransportRoute::find($routeId)->driver_id);

        // A transport_alert SMS batch was queued to the rider, guardian reached.
        $this->assertDatabaseHas('sms_batches', ['purpose' => 'transport_alert', 'total_count' => 1]);
        $this->assertDatabaseHas('sms_logs', ['student_id' => $student->id, 'purpose' => 'transport_alert']);
    }

    public function test_swap_reaches_both_student_and_primary_guardian_when_student_has_a_phone(): void
    {
        [$routeId] = $this->routeWithVehicle(capacity: 10);

        $student = $this->makeStudent(guardianPhone: '+8801711111111', userPhone: '+8801722222222');
        $this->postJson('/api/v2/transport/assignments', [
            'transport_route_id' => $routeId, 'student_id' => $student->id,
        ], $this->auth())->assertStatus(201);

        $replacement = $this->makeVehicle(capacity: 10);
        $this->postJson("/api/v2/transport/routes/{$routeId}/swap-vehicle", [
            'replacement_vehicle_id' => $replacement->id,
        ], $this->auth())->assertStatus(200);

        // Two distinct numbers => two logs for the one student.
        $this->assertDatabaseHas('sms_logs', ['student_id' => $student->id, 'recipient_phone' => '+8801711111111']);
        $this->assertDatabaseHas('sms_logs', ['student_id' => $student->id, 'recipient_phone' => '+8801722222222']);
    }

    public function test_replacement_with_insufficient_capacity_is_rejected(): void
    {
        [$routeId] = $this->routeWithVehicle(capacity: 2);

        // Seat two riders.
        foreach (range(1, 2) as $i) {
            $s = $this->makeStudent();
            $this->postJson('/api/v2/transport/assignments', [
                'transport_route_id' => $routeId, 'student_id' => $s->id,
            ], $this->auth())->assertStatus(201);
        }

        $tooSmall = $this->makeVehicle(capacity: 1);
        $this->postJson("/api/v2/transport/routes/{$routeId}/swap-vehicle", [
            'replacement_vehicle_id' => $tooSmall->id,
        ], $this->auth())->assertStatus(422);

        // No SMS on a rejected swap.
        $this->assertDatabaseMissing('sms_batches', ['purpose' => 'transport_alert']);
    }

    public function test_repaired_vehicle_returns_to_the_pool(): void
    {
        $vehicle = $this->makeVehicle(status: 'out_of_service');

        $this->patchJson("/api/v2/transport/vehicles/{$vehicle->id}/status", ['status' => 'available'], $this->auth())
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'available']);

        $this->getJson('/api/v2/transport/vehicles?status=available', $this->auth())
            ->assertStatus(200)
            ->assertJsonFragment(['registration_no' => $vehicle->registration_no]);
    }
}
