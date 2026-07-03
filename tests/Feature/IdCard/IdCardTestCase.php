<?php

namespace Tests\Feature\IdCard;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\IdCard\Models\IdCardTemplate;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Shared fixture: school -> class 8 -> section A, plus template/student/staff helpers. */
abstract class IdCardTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected School $school;
    protected AcademicYear $year;
    protected SchoolClass $class;
    protected Section $section;

    private int $studentSeq = 0;

    private int $staffSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'timezone' => 'UTC', 'is_active' => true]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 8']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);
    }

    protected function makeStudent(?int $classId = null, ?int $sectionId = null): Student
    {
        $this->studentSeq++;
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'admission_number' => "ADM-{$this->studentSeq}",
            'student_id' => "STU-{$this->studentSeq}",
            'name' => "Student {$this->studentSeq}",
            'gender' => 'male',
            'blood_group' => 'O+',
            'status' => 'active',
        ]);

        StudentAcademic::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $classId ?? $this->class->id,
            'section_id' => $sectionId ?? $this->section->id,
            'is_current' => true,
        ]);

        return $student;
    }

    protected function makeStaff(): Staff
    {
        $this->staffSeq++;
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        return Staff::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'employee_id' => "EMP-{$this->staffSeq}",
            'name' => "Staff {$this->staffSeq}",
            'gender' => 'female',
            'blood_group' => 'A+',
            'status' => 'active',
        ]);
    }

    protected function studentTemplate(array $overrides = []): IdCardTemplate
    {
        return IdCardTemplate::create(array_merge([
            'school_id' => $this->school->id,
            'type' => 'student',
            'name' => 'Default Student Card',
            'layout' => 'horizontal_classic',
            'visible_fields' => ['id', 'class_section', 'blood_group'],
            'is_default' => true,
        ], $overrides));
    }

    protected function staffTemplate(array $overrides = []): IdCardTemplate
    {
        return IdCardTemplate::create(array_merge([
            'school_id' => $this->school->id,
            'type' => 'staff',
            'name' => 'Default Staff Card',
            'layout' => 'vertical',
            'visible_fields' => ['id', 'designation', 'blood_group'],
            'is_default' => true,
        ], $overrides));
    }

    protected function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    protected function teacherToken(): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        return $user->createToken('test', ['teacher:*'])->plainTextToken;
    }

    protected function staffToken(): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        return $user->createToken('test', ['staff:*'])->plainTextToken;
    }
}
