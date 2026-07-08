<?php

namespace Tests\Feature\Transport;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Student\Models\StudentGuardian;
use App\Modules\Transport\Models\TransportVehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class TransportTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected School $school;
    protected AcademicYear $year;
    protected SchoolClass $class;
    protected Section $section;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Transport Test School',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'sms_cost_per_segment' => 0.5,
            'is_active' => true,
        ]);

        ModuleSetting::create([
            'school_id' => $this->school->id,
            'module' => 'transport',
            'is_enabled' => true,
        ]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 8']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);
    }

    protected function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    protected function teacherToken(): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole('teacher');

        return $user->createToken('test', ['teacher:*'])->plainTextToken;
    }

    protected function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->adminToken()];
    }

    /** Student with a primary guardian who has a phone; optionally give the student's own User a phone too. */
    protected function makeStudent(string $guardianPhone = '+8801700000000', ?string $userPhone = null): Student
    {
        $this->seq++;
        $user = User::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => true,
            'phone' => $userPhone,
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'admission_number' => "ADM-{$this->seq}",
            'name' => "Student {$this->seq}",
            'gender' => 'male',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'is_current' => true,
        ]);

        StudentGuardian::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'relation' => 'father',
            'name' => "Guardian of {$student->name}",
            'phone' => $guardianPhone,
            'is_primary' => true,
        ]);

        return $student;
    }

    protected function makeVehicle(int $capacity = 40, string $status = 'available'): TransportVehicle
    {
        $this->seq++;

        return TransportVehicle::create([
            'school_id' => $this->school->id,
            'registration_no' => "BUS-{$this->seq}",
            'capacity' => $capacity,
            'status' => $status,
        ]);
    }

    /** Create a route via the API (exercises FeeItem auto-creation), return its id. */
    protected function createRoute(string $name = 'Route A', float $fare = 30.0): int
    {
        $res = $this->postJson('/api/v2/transport/routes', [
            'name' => $name,
            'fare' => $fare,
        ], $this->auth());

        $res->assertStatus(201);

        return $res->json('data.id');
    }

    /** Route with an in_service vehicle attached; returns [routeId, vehicleId]. */
    protected function routeWithVehicle(int $capacity = 40, float $fare = 30.0): array
    {
        $routeId = $this->createRoute('Route ' . uniqid(), $fare);
        $vehicle = $this->makeVehicle($capacity);

        $this->putJson("/api/v2/transport/routes/{$routeId}/vehicle", [
            'vehicle_id' => $vehicle->id,
        ], $this->auth())->assertStatus(200);

        return [$routeId, $vehicle->id];
    }
}
