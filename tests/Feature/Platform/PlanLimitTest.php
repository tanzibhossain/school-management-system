<?php

namespace Tests\Feature\Platform;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Platform\Models\Plan;
use App\Modules\Staff\Models\Designation;
use App\Modules\Staff\Models\StaffIdConfig;
use App\Modules\Student\Models\StudentIdConfig;

class PlanLimitTest extends PlatformTestCase
{
    private AcademicYear $year;
    private SchoolClass $class;
    private Section $section;
    private Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->year = AcademicYear::create(['school_id' => $this->tenantSchool->id, 'year' => '2026', 'is_current' => true, 'is_trash' => false]);
        $this->class = SchoolClass::create(['school_id' => $this->tenantSchool->id, 'name' => 'Class 5', 'weight' => 5, 'is_trash' => false]);
        $this->section = Section::create(['school_id' => $this->tenantSchool->id, 'class_id' => $this->class->id, 'name' => 'A', 'is_trash' => false]);
        $this->designation = Designation::create(['school_id' => $this->tenantSchool->id, 'name' => 'Teacher']);

        StudentIdConfig::create([
            'school_id' => $this->tenantSchool->id, 'prefix' => 'SMS', 'include_year' => true,
            'year_format' => 'YYYY', 'separator' => '/', 'sequence_length' => 4,
            'reset_yearly' => true, 'last_sequence' => 0,
        ]);
        StaffIdConfig::create([
            'school_id' => $this->tenantSchool->id, 'prefix' => 'EMP', 'include_year' => true,
            'year_format' => 'YYYY', 'separator' => '/', 'sequence_length' => 4,
            'reset_yearly' => true, 'last_sequence' => 0,
        ]);
    }

    private function enrolPayload(array $overrides = []): array
    {
        return array_merge([
            'admission_number' => 'ADM-' . uniqid(),
            'name' => 'Student', 'gender' => 'male',
            'academic_year_id' => $this->year->id, 'class_id' => $this->class->id, 'section_id' => $this->section->id,
        ], $overrides);
    }

    private function hirePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Staff', 'gender' => 'male', 'employment_type' => 'permanent',
            'designation_id' => $this->designation->id,
        ], $overrides);
    }

    public function test_legacy_school_with_no_plan_is_never_capped(): void
    {
        $this->assertNull($this->tenantSchool->plan_id);

        $this->withToken($this->tenantAdminToken())
            ->postJson('/api/v2/students', $this->enrolPayload(['name' => 'Student One']))
            ->assertCreated();

        $this->withToken($this->tenantAdminToken())
            ->postJson('/api/v2/students', $this->enrolPayload(['name' => 'Student Two']))
            ->assertCreated();
    }

    public function test_student_cap_is_enforced_for_a_plan_with_a_limit(): void
    {
        $tinyPlan = Plan::create([
            'name' => 'Tiny', 'slug' => 'tiny', 'max_students' => 1, 'max_staff' => 1,
            'is_self_serve' => false, 'is_active' => true,
        ]);
        $this->tenantSchool->update(['plan_id' => $tinyPlan->id]);

        $this->withToken($this->tenantAdminToken())
            ->postJson('/api/v2/students', $this->enrolPayload(['name' => 'Within Cap']))
            ->assertCreated();

        $this->withToken($this->tenantAdminToken())
            ->postJson('/api/v2/students', $this->enrolPayload(['name' => 'Over Cap']))
            ->assertUnprocessable();
    }

    public function test_staff_cap_is_enforced_for_a_plan_with_a_limit(): void
    {
        $tinyPlan = Plan::create([
            'name' => 'Tiny', 'slug' => 'tiny', 'max_students' => 1, 'max_staff' => 1,
            'is_self_serve' => false, 'is_active' => true,
        ]);
        $this->tenantSchool->update(['plan_id' => $tinyPlan->id]);

        $this->withToken($this->tenantAdminToken())
            ->postJson('/api/v2/staff', $this->hirePayload(['name' => 'Within Cap']))
            ->assertCreated();

        $this->withToken($this->tenantAdminToken())
            ->postJson('/api/v2/staff', $this->hirePayload(['name' => 'Over Cap']))
            ->assertUnprocessable();
    }

    public function test_unlimited_plan_never_caps(): void
    {
        $this->tenantSchool->update(['plan_id' => $this->proPlan->id]);

        for ($i = 0; $i < 3; $i++) {
            $this->withToken($this->tenantAdminToken())
                ->postJson('/api/v2/students', $this->enrolPayload(['name' => "Pro Student {$i}"]))
                ->assertCreated();
        }
    }
}
