<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Examination\Models\ExamType;
use App\Modules\Mark\Models\ExamResult;
use App\Modules\Mark\Models\MarkDivision;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Marks & Results (settings, grade templates, divisions,
 * mark entry, calculation, lock).
 */
class MarksAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private AcademicYear $year;

    private SchoolClass $class;

    private Exam $exam;

    private ExamSubject $examSubject;

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

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        $section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);

        $type = ExamType::create(['school_id' => $this->school->id, 'name' => 'Final', 'is_active' => true]);
        $this->exam = Exam::create([
            'school_id' => $this->school->id, 'exam_type_id' => $type->id, 'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id, 'title' => 'Final 2026', 'start_date' => '2026-11-01', 'end_date' => '2026-11-10',
        ]);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Maths']);
        $rel = SubjectRelation::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'subject_id' => $subject->id]);
        $this->examSubject = ExamSubject::create([
            'school_id' => $this->school->id, 'exam_id' => $this->exam->id, 'subject_relation_id' => $rel->id,
            'exam_date' => '2026-11-02', 'start_time' => '09:00', 'end_time' => '11:00', 'full_marks' => 100, 'pass_marks' => 33,
        ]);

        // one enrolled student
        $this->student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Learner', 'gender' => 'male', 'admission_number' => 'ADM-1', 'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id, 'student_id' => $this->student->id, 'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id, 'section_id' => $section->id, 'is_current' => true,
        ]);
    }

    private Student $student;

    public function test_admin_can_open_mark_screens(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/mark-settings')->assertOk();
        $this->get("/admin/exams/{$this->exam->id}/marks")->assertOk();
    }

    public function test_apply_grade_template(): void
    {
        $this->actingAs($this->admin);
        $this->post("/admin/mark-settings/{$this->class->id}/grade-template", ['template' => 'bd_national_5'])->assertRedirect();
        $this->assertDatabaseHas('grade_boundaries', ['school_id' => $this->school->id, 'class_id' => $this->class->id, 'grade_label' => 'A+']);
    }

    public function test_full_mark_entry_flow(): void
    {
        $this->actingAs($this->admin);

        // grade boundaries first
        $this->post("/admin/mark-settings/{$this->class->id}/grade-template", ['template' => 'bd_national_5'])->assertRedirect();

        // add a division to the exam subject
        $this->post("/admin/exams/{$this->exam->id}/marks/divisions", [
            'exam_subject_id' => $this->examSubject->id, 'name' => 'Written', 'max_marks' => 100, 'pass_mark' => 33,
        ])->assertRedirect();
        $division = MarkDivision::where('exam_id', $this->exam->id)->firstOrFail();

        // enter a mark
        $this->post("/admin/exams/{$this->exam->id}/marks/divisions/{$division->id}/entry", [
            'marks' => [$this->student->id => 80],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('marks', ['mark_division_id' => $division->id, 'student_id' => $this->student->id, 'marks_obtained' => 80]);

        // calculate results
        $this->post("/admin/exams/{$this->exam->id}/marks/calculate")->assertSessionHasNoErrors()->assertRedirect();
        $result = ExamResult::where('exam_id', $this->exam->id)->where('student_id', $this->student->id)->firstOrFail();
        $this->assertEquals('80.00', $result->percentage);
        $this->assertTrue((bool) $result->is_pass);

        // lock
        $this->patch("/admin/exams/{$this->exam->id}/marks/lock")->assertRedirect();
        $this->assertDatabaseHas('exam_results', ['id' => $result->id, 'is_locked' => true]);
    }

    public function test_calculate_without_grade_boundaries_flashes_error(): void
    {
        $this->actingAs($this->admin);
        MarkDivision::create([
            'school_id' => $this->school->id, 'exam_id' => $this->exam->id, 'exam_subject_id' => $this->examSubject->id,
            'name' => 'Written', 'max_marks' => 100, 'display_order' => 1,
        ]);

        $this->post("/admin/exams/{$this->exam->id}/marks/calculate")->assertSessionHasErrors();
        $this->assertDatabaseCount('exam_results', 0);
    }

    public function test_cannot_delete_division_with_marks(): void
    {
        $this->actingAs($this->admin);
        $this->post("/admin/exams/{$this->exam->id}/marks/divisions", [
            'exam_subject_id' => $this->examSubject->id, 'name' => 'Written', 'max_marks' => 100,
        ])->assertRedirect();
        $division = MarkDivision::where('exam_id', $this->exam->id)->firstOrFail();

        $this->post("/admin/exams/{$this->exam->id}/marks/divisions/{$division->id}/entry", [
            'marks' => [$this->student->id => 50],
        ])->assertRedirect();

        $this->delete("/admin/exams/{$this->exam->id}/marks/divisions/{$division->id}")->assertRedirect();
        $this->assertDatabaseHas('mark_divisions', ['id' => $division->id]); // blocked
    }
}
