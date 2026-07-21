<?php

namespace Tests\Feature\Staff;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Designation;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Models\StaffIdConfig;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $year;

    private SchoolClass $class;

    private Section $section;

    private Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true, 'is_trash' => false]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 5', 'weight' => 5, 'is_trash' => false]);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A', 'is_trash' => false]);
        $this->designation = Designation::create(['school_id' => $this->school->id, 'name' => 'Teacher']);

        StaffIdConfig::create([
            'school_id' => $this->school->id,
            'prefix' => 'EMP',
            'include_year' => true,
            'year_format' => 'YYYY',
            'separator' => '/',
            'sequence_length' => 4,
            'reset_yearly' => true,
            'last_sequence' => 0,
        ]);
    }

    private function token(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    private function hirePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rahim Uddin',
            'gender' => 'male',
            'employment_type' => 'permanent',
            'designation_id' => $this->designation->id,
        ], $overrides);
    }

    public function test_admin_can_hire_staff(): void
    {
        $response = $this->withToken($this->token())
            ->postJson('/api/v2/staff', $this->hirePayload());

        $response->assertCreated()->assertJsonFragment(['name' => 'Rahim Uddin']);
        $this->assertDatabaseHas('staff', ['name' => 'Rahim Uddin', 'school_id' => $this->school->id]);
    }

    public function test_employee_id_generated_on_hire(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/staff', $this->hirePayload())
            ->assertCreated();

        $staff = Staff::where('name', 'Rahim Uddin')->first();
        $year = now()->format('Y');

        $this->assertNotNull($staff->employee_id);
        $this->assertStringStartsWith("EMP/{$year}/", $staff->employee_id);
    }

    public function test_staff_can_be_assigned_to_class(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/staff', $this->hirePayload());

        $staff = Staff::where('name', 'Rahim Uddin')->first();
        auth()->forgetGuards();

        $this->withToken($this->token())
            ->postJson("/api/v2/staff/{$staff->id}/academics", [
                'academic_year_id' => $this->year->id,
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
                'subject' => 'Mathematics',
                'is_class_teacher' => false,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('staff_academics', [
            'staff_id' => $staff->id,
            'class_id' => $this->class->id,
        ]);
    }

    public function test_only_one_class_teacher_per_section_per_year(): void
    {
        // Hire two staff
        $this->withToken($this->token())
            ->postJson('/api/v2/staff', $this->hirePayload(['name' => 'Staff One']));
        auth()->forgetGuards();

        $this->withToken($this->token())
            ->postJson('/api/v2/staff', $this->hirePayload(['name' => 'Staff Two']));
        auth()->forgetGuards();

        $staffOne = Staff::where('name', 'Staff One')->first();
        $staffTwo = Staff::where('name', 'Staff Two')->first();

        $assignPayload = [
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'is_class_teacher' => true,
        ];

        // First assignment as class teacher — should succeed
        $this->withToken($this->token())
            ->postJson("/api/v2/staff/{$staffOne->id}/academics", $assignPayload)
            ->assertCreated();
        auth()->forgetGuards();

        // Second assignment as class teacher for same section/year — should fail
        $this->withToken($this->token())
            ->postJson("/api/v2/staff/{$staffTwo->id}/academics", $assignPayload)
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'A class teacher is already assigned to this section for the selected year.']);
    }

    public function test_staff_can_be_terminated(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/staff', $this->hirePayload());

        $staff = Staff::where('name', 'Rahim Uddin')->first();
        auth()->forgetGuards();

        $this->withToken($this->token())
            ->postJson("/api/v2/staff/{$staff->id}/terminate")
            ->assertOk();

        $this->assertEquals('terminated', $staff->fresh()->status);
        $this->assertNotNull($staff->fresh()->leaving_date);
    }

    public function test_rfid_number_stored_on_hire(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/staff', $this->hirePayload(['rfid_number' => 'RFID-ABC-001']))
            ->assertCreated();

        $this->assertDatabaseHas('staff', ['rfid_number' => 'RFID-ABC-001', 'school_id' => $this->school->id]);
    }

    public function test_designation_can_be_created(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/designations', ['name' => 'Vice Principal'])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Vice Principal']);

        $this->assertDatabaseHas('designations', ['name' => 'Vice Principal', 'school_id' => $this->school->id]);
    }

    public function test_terminated_staff_can_be_rehired(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/staff', $this->hirePayload());

        $staff = Staff::where('name', 'Rahim Uddin')->first();
        $originalEmployeeId = $staff->employee_id;
        auth()->forgetGuards();

        // Terminate
        $this->withToken($this->token())
            ->postJson("/api/v2/staff/{$staff->id}/terminate")
            ->assertOk();
        auth()->forgetGuards();

        // Rehire
        $this->withToken($this->token())
            ->postJson("/api/v2/staff/{$staff->id}/re-hire", [
                'joining_date' => now()->toDateString(),
                'employment_type' => 'contractual',
            ])
            ->assertOk()
            ->assertJsonFragment(['status' => 'active']);

        $staff->refresh();
        $this->assertEquals('active', $staff->status);
        $this->assertEquals(1, $staff->re_hire_count);
        $this->assertNull($staff->leaving_date);
        // Same employee_id preserved — no new record created
        $this->assertEquals($originalEmployeeId, $staff->employee_id);
    }

    public function test_admin_can_list_staff(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/staff', $this->hirePayload());
        auth()->forgetGuards();

        $this->withToken($this->token())
            ->getJson('/api/v2/staff')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'employee_id', 'status']]]);
    }
}
