<?php

namespace Tests\Feature\Examination;

use App\Models\User;
use App\Modules\Academic\Models\AcademicGroup;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamHall;
use App\Modules\Examination\Models\ExamType;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExaminationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private AcademicYear $year;
    private SchoolClass $class;
    private Section $section;
    private ExamType $examType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school   = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin    = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create([
            'school_id'  => $this->school->id,
            'year'       => '2026',
            'is_current' => true,
        ]);

        $this->class   = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 9']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);

        $this->examType = ExamType::create([
            'school_id' => $this->school->id,
            'name'      => 'Half-Yearly',
            'is_active' => true,
        ]);
    }

    private function token(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    private function makeSubjectRelation(): SubjectRelation
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name'      => 'Mathematics',
            'sub_code'  => 'MATH',
        ]);

        return SubjectRelation::create([
            'school_id'  => $this->school->id,
            'subject_id' => $subject->id,
            'class_id'   => $this->class->id,
        ]);
    }

    private function makeExam(array $overrides = []): Exam
    {
        return Exam::create(array_merge([
            'school_id'        => $this->school->id,
            'exam_type_id'     => $this->examType->id,
            'academic_year_id' => $this->year->id,
            'class_id'         => $this->class->id,
            'title'            => 'Half-Yearly 2026',
            'start_date'       => '2026-06-01',
            'end_date'         => '2026-06-10',
            'status'           => 'draft',
            'seating_strategy' => 'sequential',
        ], $overrides));
    }

    private function makeHall(array $config = []): ExamHall
    {
        return ExamHall::create([
            'school_id'     => $this->school->id,
            'name'          => 'Main Hall',
            'layout_config' => $config ?: [
                'rows'  => 30,
                'sides' => [
                    ['label' => 'L', 'seats_per_row' => 4, 'blocked_rows' => []],
                    ['label' => 'R', 'seats_per_row' => 2, 'blocked_rows' => [23, 24, 25, 26]],
                ],
            ],
        ]);
    }

    // ── Exam type tests ────────────────────────────────────────────────────────

    public function test_admin_can_create_exam_type(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/examination/exam-types', [
                'name'      => 'Annual',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Annual']);
    }

    // ── Exam lifecycle tests ───────────────────────────────────────────────────

    public function test_admin_can_create_exam(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/examination/exams', [
                'exam_type_id'     => $this->examType->id,
                'academic_year_id' => $this->year->id,
                'class_id'         => $this->class->id,
                'title'            => 'Half-Yearly 2026',
                'start_date'       => '2026-06-01',
                'end_date'         => '2026-06-10',
                'seating_strategy' => 'interleave_group',
            ])
            ->assertCreated()
            ->assertJsonFragment(['title' => 'Half-Yearly 2026', 'status' => 'draft']);
    }

    public function test_cannot_publish_exam_without_subjects(): void
    {
        $exam = $this->makeExam();

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exams/{$exam->id}/publish")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot publish an exam with no subjects scheduled.']);
    }

    public function test_can_add_subject_and_publish_exam(): void
    {
        $exam = $this->makeExam();
        $sr   = $this->makeSubjectRelation();

        // Add subject
        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exams/{$exam->id}/subjects", [
                'subject_relation_id' => $sr->id,
                'exam_date'           => '2026-06-01',
                'start_time'          => '09:00',
                'end_time'            => '12:00',
                'full_marks'          => 100,
                'pass_marks'          => 33,
            ])
            ->assertCreated();

        // Publish
        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exams/{$exam->id}/publish")
            ->assertOk()
            ->assertJsonFragment(['status' => 'published']);
    }

    public function test_cannot_modify_completed_exam(): void
    {
        $exam = $this->makeExam(['status' => 'completed']);

        $this->withToken($this->token())
            ->putJson("/api/v2/examination/exams/{$exam->id}", ['title' => 'New Title'])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot modify a completed exam.']);
    }

    // ── Hall layout tests ──────────────────────────────────────────────────────

    public function test_admin_can_create_hall(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/examination/exam-halls', [
                'name'          => 'Main Hall',
                'layout_config' => [
                    'rows'  => 30,
                    'sides' => [
                        ['label' => 'L', 'seats_per_row' => 4, 'blocked_rows' => []],
                        ['label' => 'R', 'seats_per_row' => 2, 'blocked_rows' => [23, 24, 25, 26]],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Main Hall']);
    }

    public function test_seat_generation_produces_correct_count(): void
    {
        // 30-row hall with door blocking R side on rows 23-26
        $hall = $this->makeHall();

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exam-halls/{$hall->id}/generate-seats")
            ->assertOk()
            ->assertJsonFragment(['total_seats' => 172]);

        // Rows 1-22:  L×4 + R×2 = 6 × 22 = 132
        // Rows 23-26: L×4 + R×0 = 4 ×  4 =  16  (door blocks right side)
        // Rows 27-30: L×4 + R×2 = 6 ×  4 =  24
        $this->assertDatabaseCount('exam_hall_seats', 172);
    }

    public function test_door_blocks_right_side_rows_23_to_26(): void
    {
        $hall = $this->makeHall();

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exam-halls/{$hall->id}/generate-seats");

        // No R-side seats for rows 23-26
        $this->assertEquals(0,
            $hall->seats()->where('side', 'R')->whereBetween('row', [23, 26])->count()
        );
        // L-side still has 4 seats per row in that range
        $this->assertEquals(16,
            $hall->seats()->where('side', 'L')->whereBetween('row', [23, 26])->count()
        );
    }

    public function test_toggle_seat_availability(): void
    {
        $hall = $this->makeHall();

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exam-halls/{$hall->id}/generate-seats");

        $seat = $hall->seats()->first();
        $this->assertTrue($seat->is_available);

        $this->withToken($this->token())
            ->patchJson("/api/v2/examination/exam-halls/{$hall->id}/seats/{$seat->id}/toggle")
            ->assertOk()
            ->assertJsonFragment(['is_available' => false]);

        $this->assertDatabaseHas('exam_hall_seats', ['id' => $seat->id, 'is_available' => false]);
    }

    // ── Seating assignment tests ───────────────────────────────────────────────

    public function test_sequential_seating_assigns_all_students(): void
    {
        $exam = $this->makeExam(['seating_strategy' => 'sequential']);
        $hall = $this->makeHall();

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exam-halls/{$hall->id}/generate-seats");

        // Create 3 students in the class
        foreach (range(1, 3) as $i) {
            $student = Student::create([
                'school_id'        => $this->school->id,
                'admission_number' => "ADM-{$i}",
                'name'             => "Student {$i}",
                'gender'           => 'male',
                'status'           => 'active',
            ]);
            StudentAcademic::create([
                'school_id'        => $this->school->id,
                'student_id'       => $student->id,
                'academic_year_id' => $this->year->id,
                'class_id'         => $this->class->id,
                'section_id'       => $this->section->id,
                'roll_number'      => (string) $i,
                'is_current'       => true,
            ]);
        }

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exams/{$exam->id}/seating", [
                'hall_id' => $hall->id,
            ])
            ->assertOk()
            ->assertJsonFragment(['students_seated' => 3]);

        $this->assertDatabaseCount('exam_seating', 3);
    }

    public function test_interleave_group_mixes_students_by_group(): void
    {
        $scienceGroup = AcademicGroup::create(['school_id' => $this->school->id, 'name' => 'Science']);
        $artsGroup    = AcademicGroup::create(['school_id' => $this->school->id, 'name' => 'Arts']);

        $exam = $this->makeExam(['seating_strategy' => 'interleave_group']);
        $hall = $this->makeHall();

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exam-halls/{$hall->id}/generate-seats");

        // 2 Science + 2 Arts students
        foreach ([
            ['group_id' => $scienceGroup->id, 'roll' => '1', 'adm' => 'SCI-1'],
            ['group_id' => $scienceGroup->id, 'roll' => '2', 'adm' => 'SCI-2'],
            ['group_id' => $artsGroup->id,    'roll' => '3', 'adm' => 'ART-1'],
            ['group_id' => $artsGroup->id,    'roll' => '4', 'adm' => 'ART-2'],
        ] as $data) {
            $student = Student::create([
                'school_id'        => $this->school->id,
                'admission_number' => $data['adm'],
                'name'             => "Student {$data['adm']}",
                'gender'           => 'male',
                'status'           => 'active',
            ]);
            StudentAcademic::create([
                'school_id'        => $this->school->id,
                'student_id'       => $student->id,
                'academic_year_id' => $this->year->id,
                'class_id'         => $this->class->id,
                'section_id'       => $this->section->id,
                'group_id'         => $data['group_id'],
                'roll_number'      => $data['roll'],
                'is_current'       => true,
            ]);
        }

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exams/{$exam->id}/seating", [
                'hall_id'  => $hall->id,
                'strategy' => 'interleave_group',
            ])
            ->assertOk()
            ->assertJsonFragment(['students_seated' => 4]);

        // Seats 1,3 should be Science; seats 2,4 should be Arts (interleaved)
        $seating = \App\Modules\Examination\Models\ExamSeating::where('exam_id', $exam->id)
            ->orderBy('exam_roll')
            ->get();

        $this->assertEquals($scienceGroup->id, $seating[0]->group_id); // roll 0001
        $this->assertEquals($artsGroup->id,    $seating[1]->group_id); // roll 0002
        $this->assertEquals($scienceGroup->id, $seating[2]->group_id); // roll 0003
        $this->assertEquals($artsGroup->id,    $seating[3]->group_id); // roll 0004
    }

    public function test_anti_adjacency_prevents_same_group_front_back_adjacency(): void
    {
        $scienceGroup = AcademicGroup::create(['school_id' => $this->school->id, 'name' => 'Science']);
        $artsGroup    = AcademicGroup::create(['school_id' => $this->school->id, 'name' => 'Arts']);

        $exam = $this->makeExam(['seating_strategy' => 'anti_adjacency']);

        // 2 rows × 4 L-seats = 8 seats total
        $hall = $this->makeHall([
            'rows'  => 2,
            'sides' => [['label' => 'L', 'seats_per_row' => 4, 'blocked_rows' => []]],
        ]);

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exam-halls/{$hall->id}/generate-seats");

        // 4 Science + 4 Arts
        foreach ([
            ['group_id' => $scienceGroup->id, 'roll' => '1', 'adm' => 'SCI-A1'],
            ['group_id' => $scienceGroup->id, 'roll' => '2', 'adm' => 'SCI-A2'],
            ['group_id' => $scienceGroup->id, 'roll' => '3', 'adm' => 'SCI-A3'],
            ['group_id' => $scienceGroup->id, 'roll' => '4', 'adm' => 'SCI-A4'],
            ['group_id' => $artsGroup->id,    'roll' => '5', 'adm' => 'ART-A1'],
            ['group_id' => $artsGroup->id,    'roll' => '6', 'adm' => 'ART-A2'],
            ['group_id' => $artsGroup->id,    'roll' => '7', 'adm' => 'ART-A3'],
            ['group_id' => $artsGroup->id,    'roll' => '8', 'adm' => 'ART-A4'],
        ] as $d) {
            $student = Student::create([
                'school_id' => $this->school->id, 'admission_number' => $d['adm'],
                'name' => "Student {$d['adm']}", 'gender' => 'male', 'status' => 'active',
            ]);
            StudentAcademic::create([
                'school_id' => $this->school->id, 'student_id' => $student->id,
                'academic_year_id' => $this->year->id, 'class_id' => $this->class->id,
                'section_id' => $this->section->id, 'group_id' => $d['group_id'],
                'roll_number' => $d['roll'], 'is_current' => true,
            ]);
        }

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exams/{$exam->id}/seating", [
                'hall_id'  => $hall->id,
                'strategy' => 'anti_adjacency',
            ])
            ->assertOk()
            ->assertJsonFragment(['students_seated' => 8]);

        // Row 1 (rowOffset=0): L1=Sci, L2=Arts, L3=Sci, L4=Arts  (rolls 0001–0004)
        // Row 2 (rowOffset=1): L1=Arts, L2=Sci, L3=Arts, L4=Sci  (rolls 0005–0008)
        //
        // Column L1: Sci, Arts  → rolls 0001 vs 0005 differ ✓
        // Column L2: Arts, Sci  → rolls 0002 vs 0006 differ ✓
        $seating = \App\Modules\Examination\Models\ExamSeating::where('exam_id', $exam->id)
            ->orderBy('exam_roll')
            ->get();

        $this->assertCount(8, $seating);

        // Column L1 front/back: row1-L1 vs row2-L1 must differ
        $this->assertNotEquals($seating[0]->group_id, $seating[4]->group_id,
            'Column L1: rows 1 and 2 must have different groups (anti-adjacency)');

        // Column L2 front/back
        $this->assertNotEquals($seating[1]->group_id, $seating[5]->group_id,
            'Column L2: rows 1 and 2 must have different groups');

        // Within row 1: adjacent seats must differ
        $this->assertNotEquals($seating[0]->group_id, $seating[1]->group_id,
            'Row 1: seats 1 and 2 must have different groups');
        $this->assertNotEquals($seating[1]->group_id, $seating[2]->group_id,
            'Row 1: seats 2 and 3 must have different groups');
    }

    public function test_blank_every_leaves_empty_seats(): void
    {
        $exam = $this->makeExam(['seating_strategy' => 'sequential']);

        // 6-seat hall (1 row × 6 seats on L side)
        $hall = $this->makeHall([
            'rows'  => 1,
            'sides' => [['label' => 'L', 'seats_per_row' => 6, 'blocked_rows' => []]],
        ]);

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exam-halls/{$hall->id}/generate-seats");

        // 4 students, blank_every=2 → seats used = 0,1,3,4 (skip 2 and 5) → 4 students fit
        foreach (range(1, 4) as $i) {
            $student = Student::create([
                'school_id'        => $this->school->id,
                'admission_number' => "ADM-BLK-{$i}",
                'name'             => "Student {$i}",
                'gender'           => 'male',
                'status'           => 'active',
            ]);
            StudentAcademic::create([
                'school_id'        => $this->school->id,
                'student_id'       => $student->id,
                'academic_year_id' => $this->year->id,
                'class_id'         => $this->class->id,
                'section_id'       => $this->section->id,
                'roll_number'      => (string) $i,
                'is_current'       => true,
            ]);
        }

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exams/{$exam->id}/seating", [
                'hall_id'     => $hall->id,
                'blank_every' => 2,
            ])
            ->assertOk()
            ->assertJsonFragment(['students_seated' => 4]);

        // Only 4 of the 6 seats should have an assignment
        $this->assertDatabaseCount('exam_seating', 4);
    }

    public function test_cannot_seat_more_students_than_available_seats(): void
    {
        $exam = $this->makeExam();

        // Hall with only 2 seats
        $hall = $this->makeHall([
            'rows'  => 1,
            'sides' => [['label' => 'L', 'seats_per_row' => 2, 'blocked_rows' => []]],
        ]);

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exam-halls/{$hall->id}/generate-seats");

        // Create 3 students (more than 2 seats)
        foreach (range(1, 3) as $i) {
            $student = Student::create([
                'school_id'        => $this->school->id,
                'admission_number' => "ADM-OVER-{$i}",
                'name'             => "Student {$i}",
                'gender'           => 'male',
                'status'           => 'active',
            ]);
            StudentAcademic::create([
                'school_id'        => $this->school->id,
                'student_id'       => $student->id,
                'academic_year_id' => $this->year->id,
                'class_id'         => $this->class->id,
                'section_id'       => $this->section->id,
                'roll_number'      => (string) $i,
                'is_current'       => true,
            ]);
        }

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exams/{$exam->id}/seating", [
                'hall_id' => $hall->id,
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Not enough seats (2) for 3 students in this hall.']);
    }

    public function test_clear_seating_removes_all_assignments(): void
    {
        $exam = $this->makeExam();
        $hall = $this->makeHall();

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exam-halls/{$hall->id}/generate-seats");

        $student = Student::create([
            'school_id'        => $this->school->id,
            'admission_number' => 'ADM-CLR',
            'name'             => 'Clear Test',
            'gender'           => 'male',
            'status'           => 'active',
        ]);
        StudentAcademic::create([
            'school_id'        => $this->school->id,
            'student_id'       => $student->id,
            'academic_year_id' => $this->year->id,
            'class_id'         => $this->class->id,
            'section_id'       => $this->section->id,
            'roll_number'      => '1',
            'is_current'       => true,
        ]);

        $this->withToken($this->token())
            ->postJson("/api/v2/examination/exams/{$exam->id}/seating", ['hall_id' => $hall->id]);

        $this->assertDatabaseCount('exam_seating', 1);

        $this->withToken($this->token())
            ->deleteJson("/api/v2/examination/exams/{$exam->id}/seating")
            ->assertOk();

        $this->assertDatabaseCount('exam_seating', 0);
    }
}
