<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamHall;
use App\Modules\Examination\Models\ExamHallSeat;
use App\Modules\Examination\Models\ExamType;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Exam seating (halls, seat generation/toggle, per-exam assignment).
 */
class ExamSeatingAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private Exam $exam;

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

        $year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        $section = Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A']);
        $type = ExamType::create(['school_id' => $this->school->id, 'name' => 'Final', 'is_active' => true]);
        $this->exam = Exam::create([
            'school_id' => $this->school->id, 'exam_type_id' => $type->id, 'academic_year_id' => $year->id,
            'class_id' => $class->id, 'title' => 'Final 2026', 'start_date' => '2026-11-01', 'end_date' => '2026-11-10',
        ]);

        foreach (['ADM-1', 'ADM-2', 'ADM-3'] as $adm) {
            $student = Student::create([
                'school_id' => $this->school->id, 'name' => 'S ' . $adm, 'gender' => 'male',
                'admission_number' => $adm, 'status' => 'active',
            ]);
            StudentAcademic::create([
                'school_id' => $this->school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id,
                'class_id' => $class->id, 'section_id' => $section->id, 'is_current' => true,
            ]);
        }
    }

    private function makeHall(int $rows = 5, int $left = 3, int $right = 3): ExamHall
    {
        $this->post('/admin/exam-halls', [
            'name' => 'Main Hall', 'rows' => $rows, 'left_per_row' => $left, 'right_per_row' => $right,
        ])->assertRedirect();

        return ExamHall::where('school_id', $this->school->id)->firstOrFail();
    }

    public function test_admin_can_open_seating_screens(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/exam-halls')->assertOk();
        $this->get("/admin/exams/{$this->exam->id}/seating")->assertOk();
    }

    public function test_creating_hall_generates_seats(): void
    {
        $this->actingAs($this->admin);
        $hall = $this->makeHall(5, 3, 3);

        $this->assertEquals(30, ExamHallSeat::where('hall_id', $hall->id)->count());
        $this->get("/admin/exam-halls/{$hall->id}")->assertOk();
    }

    public function test_can_toggle_and_delete_seat_and_hall(): void
    {
        $this->actingAs($this->admin);
        $hall = $this->makeHall(2, 2, 0); // 4 seats
        $seat = ExamHallSeat::where('hall_id', $hall->id)->firstOrFail();

        $this->patch("/admin/exam-halls/{$hall->id}/seats/{$seat->id}/toggle")->assertRedirect();
        $this->assertFalse((bool) $seat->fresh()->is_available);

        $this->delete("/admin/exam-halls/{$hall->id}")->assertRedirect();
        $this->assertDatabaseMissing('exam_halls', ['id' => $hall->id]);
    }

    public function test_assign_and_clear_seating(): void
    {
        $this->actingAs($this->admin);
        $hall = $this->makeHall(5, 3, 3); // 30 seats, 3 students

        $this->post("/admin/exams/{$this->exam->id}/seating", [
            'hall_id' => $hall->id, 'strategy' => 'sequential',
        ])->assertRedirect();
        $this->assertEquals(3, \App\Modules\Examination\Models\ExamSeating::where('exam_id', $this->exam->id)->count());

        // hall now has assignments → deletion blocked
        $this->delete("/admin/exam-halls/{$hall->id}")->assertRedirect();
        $this->assertDatabaseHas('exam_halls', ['id' => $hall->id]);

        // clear then it's deletable
        $this->delete("/admin/exams/{$this->exam->id}/seating")->assertRedirect();
        $this->assertEquals(0, \App\Modules\Examination\Models\ExamSeating::where('exam_id', $this->exam->id)->count());
    }

    public function test_assign_fails_when_not_enough_seats(): void
    {
        $this->actingAs($this->admin);
        $hall = $this->makeHall(1, 2, 0); // 2 seats, 3 students

        $this->post("/admin/exams/{$this->exam->id}/seating", ['hall_id' => $hall->id, 'strategy' => 'sequential'])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertEquals(0, \App\Modules\Examination\Models\ExamSeating::where('exam_id', $this->exam->id)->count());
    }
}
