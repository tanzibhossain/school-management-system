<?php

namespace Tests\Feature\LMS;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\Subject;
use App\Modules\LMS\Models\Course;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Shared fixture: school (LMS enabled + AI key set) -> class -> subject -> a course taught by $this->staff. */
abstract class LMSTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected School $school;

    protected AcademicYear $year;

    protected SchoolClass $class;

    protected Section $section;

    protected Subject $subject;

    protected User $teacherUser;

    protected Staff $staff;

    protected Course $course;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'lms_ai_api_key' => 'test-anthropic-key',
            'is_active' => true,
        ]);

        ModuleSetting::create(['school_id' => $this->school->id, 'module' => 'lms', 'is_enabled' => true]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 8']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);
        $this->subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Mathematics']);

        $this->teacherUser = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->teacherUser->assignRole('teacher');
        $this->staff = Staff::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'name' => 'Teacher One',
            'gender' => 'female',
            'status' => 'active',
            'joining_date' => now()->subYear()->toDateString(),
        ]);

        $this->course = Course::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->staff->id,
            'title' => 'Algebra Basics',
            'is_active' => true,
        ]);
    }

    protected function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    protected function teacherToken(): string
    {
        return $this->teacherUser->createToken('test', User::abilitiesForRole('teacher'))->plainTextToken;
    }

    /** A second teacher who does NOT own $this->course. */
    protected function otherTeacherToken(): array
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole('teacher');
        $staff = Staff::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'name' => 'Teacher Two',
            'gender' => 'male',
            'status' => 'active',
        ]);

        return [$user->createToken('test', User::abilitiesForRole('teacher'))->plainTextToken, $staff];
    }

    /** A student enrolled in $this->class (so they see $this->course). */
    protected function makeStudent(): array
    {
        $this->seq++;
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole('student');

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

        $token = $user->createToken('test', User::abilitiesForRole('student'))->plainTextToken;

        return [$token, $student];
    }
}
