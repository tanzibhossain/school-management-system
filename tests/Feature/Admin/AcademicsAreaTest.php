<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamType;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Academics area (attendance register, exam types, exams + subjects).
 */
class AcademicsAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private AcademicYear $year;

    private SchoolClass $class;

    private Section $section;

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
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);
    }

    private function enrol(string $admission): Student
    {
        $student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Student ' . $admission,
            'gender' => 'male', 'admission_number' => $admission, 'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id, 'student_id' => $student->id,
            'academic_year_id' => $this->year->id, 'class_id' => $this->class->id,
            'section_id' => $this->section->id, 'is_current' => true,
        ]);

        return $student;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/exams')->assertRedirect('/login');
    }

    public function test_admin_can_open_academics_screens(): void
    {
        $this->actingAs($this->admin);
        foreach (['/admin/attendance', '/admin/exam-types', '/admin/exams'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_attendance_register_loads_roster(): void
    {
        $this->actingAs($this->admin);
        $this->enrol('ADM-1');

        $this->get('/admin/attendance?class_id=' . $this->class->id . '&section_id=' . $this->section->id)
            ->assertOk()
            ->assertSee('Student ADM-1');
    }

    public function test_can_record_attendance(): void
    {
        $this->actingAs($this->admin);
        $s1 = $this->enrol('ADM-1');
        $s2 = $this->enrol('ADM-2');
        $date = now()->format('Y-m-d');

        $this->post('/admin/attendance', [
            'class_id' => $this->class->id, 'section_id' => $this->section->id, 'date' => $date,
            'statuses' => [$s1->id => 'present', $s2->id => 'absent'],
        ])->assertRedirect();

        $this->assertDatabaseHas('student_attendances', ['student_id' => $s1->id, 'status' => 'present']);
        $this->assertDatabaseHas('student_attendances', ['student_id' => $s2->id, 'status' => 'absent']);
    }

    public function test_recording_attendance_again_updates_not_duplicates(): void
    {
        $this->actingAs($this->admin);
        $s1 = $this->enrol('ADM-1');
        $date = now()->format('Y-m-d');
        $payload = fn (string $status) => [
            'class_id' => $this->class->id, 'section_id' => $this->section->id, 'date' => $date,
            'statuses' => [$s1->id => $status],
        ];

        $this->post('/admin/attendance', $payload('present'))->assertRedirect();
        $this->post('/admin/attendance', $payload('late'))->assertRedirect();

        $this->assertEquals(1, \App\Modules\Attendance\Models\StudentAttendance::where('student_id', $s1->id)->count());
        $this->assertDatabaseHas('student_attendances', ['student_id' => $s1->id, 'status' => 'late']);
    }

    // ── Exams ───────────────────────────────────────────────────────────────────

    public function test_exam_type_crud_and_delete_guard(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/exam-types', ['name' => 'Midterm'])->assertRedirect();
        $type = ExamType::where('school_id', $this->school->id)->firstOrFail();

        Exam::create([
            'school_id' => $this->school->id, 'exam_type_id' => $type->id, 'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id, 'title' => 'Midterm 2026', 'start_date' => '2026-06-01', 'end_date' => '2026-06-10',
        ]);

        $this->delete("/admin/exam-types/{$type->id}")->assertRedirect();
        $this->assertDatabaseHas('exam_types', ['id' => $type->id]); // blocked
    }

    public function test_exam_lifecycle_publish_requires_subjects(): void
    {
        $this->actingAs($this->admin);
        $type = ExamType::create(['school_id' => $this->school->id, 'name' => 'Final', 'is_active' => true]);

        $this->post('/admin/exams', [
            'exam_type_id' => $type->id, 'academic_year_id' => $this->year->id, 'class_id' => $this->class->id,
            'title' => 'Final 2026', 'start_date' => '2026-11-01', 'end_date' => '2026-11-10',
        ])->assertRedirect();

        $exam = Exam::where('school_id', $this->school->id)->firstOrFail();
        $this->assertEquals('draft', $exam->status);

        // Publish with no subjects → blocked
        $this->patch("/admin/exams/{$exam->id}/publish")->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'status' => 'draft']);

        // Add a subject, then publish + complete
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Maths']);
        $rel = SubjectRelation::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'subject_id' => $subject->id]);

        $this->post("/admin/exams/{$exam->id}/subjects", [
            'subject_relation_id' => $rel->id, 'exam_date' => '2026-11-02',
            'start_time' => '09:00', 'end_time' => '11:00', 'full_marks' => 100, 'pass_marks' => 33,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('exam_subjects', ['exam_id' => $exam->id, 'subject_relation_id' => $rel->id]);

        $this->patch("/admin/exams/{$exam->id}/publish")->assertRedirect();
        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'status' => 'published']);

        $this->patch("/admin/exams/{$exam->id}/complete")->assertRedirect();
        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'status' => 'completed']);
    }
}
