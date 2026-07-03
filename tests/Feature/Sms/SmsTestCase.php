<?php

namespace Tests\Feature\Sms;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Student\Models\StudentGuardian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Shared fixture: school (with an sms_cost_per_segment rate) -> class 8 -> section A. */
abstract class SmsTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected School $school;
    protected AcademicYear $year;
    protected SchoolClass $class;
    protected Section $section;

    private int $studentSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'sms_cost_per_segment' => 0.5,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 8']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);
    }

    /** Student with a primary guardian who HAS a phone on file — the normal case. */
    protected function makeStudent(?int $classId = null, ?int $sectionId = null, string $phone = '+8801700000000'): Student
    {
        $student = $this->makeStudentWithoutGuardian($classId, $sectionId);

        StudentGuardian::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'relation' => 'father',
            'name' => "Guardian of {$student->name}",
            'phone' => $phone,
            'is_primary' => true,
        ]);

        return $student;
    }

    /** Student with a primary guardian who has NO phone on file — exercises the failure path. */
    protected function makeStudentWithGuardianButNoPhone(?int $classId = null, ?int $sectionId = null): Student
    {
        $student = $this->makeStudentWithoutGuardian($classId, $sectionId);

        StudentGuardian::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'relation' => 'mother',
            'name' => "Guardian of {$student->name}",
            'phone' => null,
            'is_primary' => true,
        ]);

        return $student;
    }

    private function makeStudentWithoutGuardian(?int $classId = null, ?int $sectionId = null): Student
    {
        $this->studentSeq++;
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'admission_number' => "ADM-{$this->studentSeq}",
            'name' => "Student {$this->studentSeq}",
            'gender' => 'male',
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

    protected function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    protected function teacherToken(): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        return $user->createToken('test', ['teacher:*'])->plainTextToken;
    }

    protected function accountantToken(): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole('accountant');

        return $user->createToken('test', ['accountant:*'])->plainTextToken;
    }
}
