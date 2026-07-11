<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Transport\Models\StudentTransportAssignment;
use App\Modules\Transport\Models\TransportDriver;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\TransportVehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Transport optional module (gating, drivers, vehicles, routes,
 * vehicle assignment, rider assignment/end).
 */
class TransportModuleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    private function enable(): void
    {
        ModuleSetting::create(['school_id' => $this->school->id, 'module' => 'transport', 'is_enabled' => true]);
    }

    public function test_403_when_disabled(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/transport/routes')->assertForbidden();
    }

    public function test_screens_load_when_enabled(): void
    {
        $this->actingAs($this->admin);
        $this->enable();
        foreach (['/admin/transport/routes', '/admin/transport/vehicles', '/admin/transport/drivers'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_create_driver_vehicle_route(): void
    {
        $this->actingAs($this->admin);
        $this->enable();

        $this->post('/admin/transport/drivers', ['name' => 'Mr Bus', 'status' => 'active'])->assertRedirect();
        $this->assertDatabaseHas('transport_drivers', ['school_id' => $this->school->id, 'name' => 'Mr Bus']);

        $this->post('/admin/transport/vehicles', ['registration_no' => 'DHK-1', 'capacity' => 30])->assertRedirect();
        $this->assertDatabaseHas('transport_vehicles', ['school_id' => $this->school->id, 'registration_no' => 'DHK-1', 'status' => 'available']);

        $this->post('/admin/transport/routes', ['name' => 'Route A', 'fare' => 500])->assertRedirect();
        $this->assertDatabaseHas('transport_routes', ['school_id' => $this->school->id, 'name' => 'Route A']);
    }

    public function test_set_vehicle_and_assign_rider_flow(): void
    {
        $this->actingAs($this->admin);
        $this->enable();

        $vehicle = TransportVehicle::create(['school_id' => $this->school->id, 'registration_no' => 'DHK-9', 'capacity' => 2, 'status' => 'available']);
        $route = TransportRoute::create(['school_id' => $this->school->id, 'name' => 'Route A', 'fare' => 0, 'is_active' => true]);
        $student = Student::create(['school_id' => $this->school->id, 'name' => 'Rider', 'gender' => 'male', 'admission_number' => 'ADM-1', 'status' => 'active']);

        // attach vehicle → goes in_service
        $this->patch("/admin/transport/routes/{$route->id}/vehicle", ['vehicle_id' => $vehicle->id])->assertRedirect();
        $this->assertDatabaseHas('transport_vehicles', ['id' => $vehicle->id, 'status' => 'in_service']);
        $this->assertEquals($vehicle->id, $route->fresh()->current_vehicle_id);

        // assign rider
        $this->post("/admin/transport/routes/{$route->id}/riders", ['student_id' => $student->id, 'pickup_point' => 'Gate 1'])->assertRedirect();
        $assignment = StudentTransportAssignment::where('transport_route_id', $route->id)->firstOrFail();
        $this->assertEquals('active', $assignment->status);

        // end rider
        $this->patch("/admin/transport/routes/{$route->id}/riders/{$assignment->id}/end")->assertRedirect();
        $this->assertEquals('ended', $assignment->fresh()->status);
    }

    public function test_cannot_assign_rider_without_vehicle(): void
    {
        $this->actingAs($this->admin);
        $this->enable();

        $route = TransportRoute::create(['school_id' => $this->school->id, 'name' => 'Route B', 'fare' => 0, 'is_active' => true]);
        $student = Student::create(['school_id' => $this->school->id, 'name' => 'Rider', 'gender' => 'male', 'admission_number' => 'ADM-2', 'status' => 'active']);

        $this->post("/admin/transport/routes/{$route->id}/riders", ['student_id' => $student->id])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('student_transport_assignments', 0);
    }
}
