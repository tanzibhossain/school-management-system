<?php

namespace Tests\Feature\Certificate;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Examination\Models\ExamType;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shared fixture: school → class 8 → exam with one subject → one student.
 * Mirrors tests/Feature/Mark/MarkTestCase.php's setup.
 */
abstract class CertificateTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected School $school;

    protected AcademicYear $year;

    protected SchoolClass $class;

    protected Section $section;

    protected Exam $exam;

    protected ExamSubject $examSubject;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'timezone' => 'UTC', 'is_active' => true]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 8']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);

        $type = ExamType::create(['school_id' => $this->school->id, 'name' => 'Annual']);

        $this->exam = Exam::create([
            'school_id' => $this->school->id,
            'exam_type_id' => $type->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'title' => 'Annual Exam 2026',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'status' => 'published',
        ]);

        $subject = Subject::firstOrCreate(['school_id' => $this->school->id, 'name' => 'Math'], ['weight' => 0]);
        $relation = SubjectRelation::firstOrCreate([
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'class_id' => $this->class->id,
        ]);

        $this->examSubject = ExamSubject::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'subject_relation_id' => $relation->id,
            'exam_date' => '2026-11-01',
            'start_time' => '10:00',
            'end_time' => '13:00',
            'full_marks' => 100,
            'pass_marks' => 33,
        ]);

        $this->student = $this->makeStudent('ADM-001');
    }

    protected function makeStudent(string $admissionNumber): Student
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'admission_number' => $admissionNumber,
            'name' => "Student {$admissionNumber}",
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

    protected function staffToken(): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        return $user->createToken('test', ['staff:*'])->plainTextToken;
    }
}
