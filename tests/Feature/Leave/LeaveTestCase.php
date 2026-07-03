<?php

namespace Tests\Feature\Leave;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Leave\Models\LeaveType;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shared fixture: school → class/section → one student (enrolled current
 * academic year) → one staff member. No school_opening_hours rows are
 * created by default — WorkingDayService fails open (assumes every weekday
 * is open) when no config row exists, so individual tests only add the rows
 * or holidays needed to exercise a specific exclusion.
 */
abstract class LeaveTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected School $school;
    protected AcademicYear $year;
    protected SchoolClass $class;
    protected Section $section;
    protected Student $student;
    protected Staff $staff;
    protected User $staffUser;
    protected LeaveType $studentLeaveType;
    protected LeaveType $staffLeaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'timezone' => 'UTC', 'is_active' => true]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year    = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $this->class   = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 5']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);

        $this->student = $this->makeStudent('ADM-001');

        $this->staffUser = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->staff     = Staff::create([
            'school_id' => $this->school->id,
            'user_id'   => $this->staffUser->id,
            'name'      => 'Staff One',
            'gender'    => 'female',
        ]);

        // Generous default limit so tests exercising a full Mon–Fri range don't
        // incidentally trip balance enforcement; the balance test tightens its own type.
        $this->studentLeaveType = LeaveType::create([
            'school_id'         => $this->school->id,
            'name'              => 'Sick Leave',
            'applies_to'        => 'student',
            'max_days_per_year' => 10,
        ]);

        $this->staffLeaveType = LeaveType::create([
            'school_id'         => $this->school->id,
            'name'              => 'Casual Leave',
            'applies_to'        => 'staff',
            'max_days_per_year' => 10,
        ]);
    }

    protected function makeStudent(string $admissionNumber): Student
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $student = Student::create([
            'school_id'        => $this->school->id,
            'user_id'          => $user->id,
            'admission_number' => $admissionNumber,
            'name'             => "Student {$admissionNumber}",
            'gender'           => 'male',
            'status'           => 'active',
        ]);

        StudentAcademic::create([
            'school_id'        => $this->school->id,
            'student_id'       => $student->id,
            'academic_year_id' => $this->year->id,
            'class_id'         => $this->class->id,
            'section_id'       => $this->section->id,
            'is_current'       => true,
        ]);

        return $student;
    }

    protected function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    protected function teacherToken(?int $classTeacherOfSection = null): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $staff = Staff::create([
            'school_id' => $this->school->id,
            'user_id'   => $user->id,
            'name'      => 'Teacher One',
            'gender'    => 'female',
        ]);

        if ($classTeacherOfSection !== null) {
            Section::whereKey($classTeacherOfSection)->update(['class_teacher_id' => $staff->id]);
        }

        return $user->createToken('test', ['teacher:*'])->plainTextToken;
    }

    protected function staffToken(): string
    {
        return $this->staffUser->createToken('test', ['staff:*'])->plainTextToken;
    }
}
