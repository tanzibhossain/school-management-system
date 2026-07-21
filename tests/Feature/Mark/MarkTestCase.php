<?php

namespace Tests\Feature\Mark;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Examination\Models\ExamType;
use App\Modules\Mark\Models\Mark;
use App\Modules\Mark\Models\MarkDivision;
use App\Modules\Mark\Services\GradeTemplateService;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Student\Models\StudentSubject;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shared fixture: school → class 8 → exam with Math + English (+ optional Music),
 * bd_national_5 boundaries, Mid(40)/Final(60) divisions per subject.
 */
abstract class MarkTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected School $school;

    protected AcademicYear $year;

    protected SchoolClass $class;

    protected Section $section;

    protected Exam $exam;

    /** @var array<string, ExamSubject> keyed by subject name */
    protected array $examSubjects = [];

    /** @var array<string, array{mid: MarkDivision, final: MarkDivision}> */
    protected array $divisions = [];

    protected Student $student;   // enrolled in all three, Music optional

    protected Student $student2;  // enrolled in Math + English only (Music = N/A)

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

        // BD national boundaries for the class
        app(GradeTemplateService::class)->applyGradeTemplate($this->school->id, $this->class->id, 'bd_national_5');

        // Exam
        $type = ExamType::create(['school_id' => $this->school->id, 'name' => 'Half-Yearly']);

        $this->exam = Exam::create([
            'school_id' => $this->school->id,
            'exam_type_id' => $type->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'title' => 'Half-Yearly 2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-10',
            'status' => 'published',
        ]);

        // Subjects: Math, English (compulsory), Music (optional 4th)
        foreach (['Math', 'English', 'Music'] as $name) {
            $this->addSubjectToExam($name, $this->exam);
        }

        // Students
        $this->student = $this->makeStudent('ADM-001');
        $this->student2 = $this->makeStudent('ADM-002');

        $this->enroll($this->student, ['Math' => false, 'English' => false, 'Music' => true]);
        $this->enroll($this->student2, ['Math' => false, 'English' => false]); // no Music → N/A
    }

    /** Create subject + relation + exam_subject + Mid(40)/Final(60) divisions. */
    protected function addSubjectToExam(string $name, Exam $exam, ?int $combinedGroup = null, float $fullMarks = 100, float $passMarks = 33): ExamSubject
    {
        $subject = Subject::firstOrCreate(['school_id' => $this->school->id, 'name' => $name], ['weight' => 0]);

        $relation = SubjectRelation::firstOrCreate([
            'school_id' => $this->school->id,
            'subject_id' => $subject->id,
            'class_id' => $this->class->id,
        ]);

        $examSubject = ExamSubject::create([
            'school_id' => $this->school->id,
            'exam_id' => $exam->id,
            'subject_relation_id' => $relation->id,
            'exam_date' => '2026-06-02',
            'start_time' => '10:00',
            'end_time' => '13:00',
            'full_marks' => $fullMarks,
            'pass_marks' => $passMarks,
            'combined_group' => $combinedGroup,
        ]);

        $mid = MarkDivision::create([
            'school_id' => $this->school->id, 'exam_id' => $exam->id,
            'exam_subject_id' => $examSubject->id,
            'name' => 'Mid', 'max_marks' => $fullMarks * 0.4, 'display_order' => 0,
        ]);
        $final = MarkDivision::create([
            'school_id' => $this->school->id, 'exam_id' => $exam->id,
            'exam_subject_id' => $examSubject->id,
            'name' => 'Final', 'max_marks' => $fullMarks * 0.6, 'display_order' => 1,
        ]);

        $this->examSubjects[$name] = $examSubject;
        $this->divisions[$name] = ['mid' => $mid, 'final' => $final];

        return $examSubject;
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

    /** @param  array<string, bool>  $subjects  name => is_optional */
    protected function enroll(Student $student, array $subjects): void
    {
        foreach ($subjects as $name => $isOptional) {
            StudentSubject::create([
                'school_id' => $this->school->id,
                'student_id' => $student->id,
                'subject_relation_id' => $this->examSubjects[$name]->subject_relation_id,
                'academic_year_id' => $this->year->id,
                'is_optional' => $isOptional,
            ]);
        }
    }

    /** Directly seed marks: percent split across Mid (40%) and Final (60%). */
    protected function giveMarks(Student $student, string $subject, float $total, bool $absent = false): void
    {
        foreach (['mid' => 0.4, 'final' => 0.6] as $key => $share) {
            Mark::create([
                'school_id' => $this->school->id,
                'exam_id' => $this->exam->id,
                'student_id' => $student->id,
                'mark_division_id' => $this->divisions[$subject][$key]->id,
                'marks_obtained' => $absent ? null : round($total * $share, 2),
                'is_absent' => $absent,
                'entered_by' => $this->admin->id,
            ]);
        }
    }

    protected function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }
}
