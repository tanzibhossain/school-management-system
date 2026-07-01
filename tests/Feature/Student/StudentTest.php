<?php

namespace Tests\Feature\Student;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentIdConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private AcademicYear $year;
    private SchoolClass $class;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school  = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin   = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year    = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2026', 'is_current' => true, 'is_trash' => false]);
        $this->class   = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 5', 'weight' => 5, 'is_trash' => false]);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A', 'is_trash' => false]);

        StudentIdConfig::create([
            'school_id'       => $this->school->id,
            'prefix'          => 'SMS',
            'include_year'    => true,
            'year_format'     => 'YYYY',
            'separator'       => '/',
            'sequence_length' => 4,
            'reset_yearly'    => true,
            'last_sequence'   => 0,
        ]);
    }

    private function token(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    private function enrolPayload(array $overrides = []): array
    {
        return array_merge([
            'admission_number' => 'ADM-001',
            'name'             => 'John Doe',
            'gender'           => 'male',
            'academic_year_id' => $this->year->id,
            'class_id'         => $this->class->id,
            'section_id'       => $this->section->id,
        ], $overrides);
    }

    public function test_admin_can_enrol_student(): void
    {
        $response = $this->withToken($this->token())
            ->postJson('/api/v2/students', $this->enrolPayload());

        $response->assertCreated()->assertJsonFragment(['name' => 'John Doe']);
        $this->assertDatabaseHas('students', ['admission_number' => 'ADM-001', 'school_id' => $this->school->id]);
        $this->assertDatabaseHas('student_academics', ['school_id' => $this->school->id, 'is_current' => 1]);
    }

    public function test_student_id_generated_on_enrolment(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/students', $this->enrolPayload())
            ->assertCreated();

        $student = Student::where('admission_number', 'ADM-001')->first();
        $year    = now()->format('Y');

        $this->assertNotNull($student->student_id);
        $this->assertStringStartsWith("SMS/{$year}/", $student->student_id);
    }

    public function test_enrol_rejects_when_section_is_full(): void
    {
        $this->section->update(['capacity' => 1]);

        // Enrol first student
        $this->withToken($this->token())
            ->postJson('/api/v2/students', $this->enrolPayload(['admission_number' => 'ADM-001']));

        // Second should fail
        $this->withToken($this->token())
            ->postJson('/api/v2/students', $this->enrolPayload(['admission_number' => 'ADM-002']))
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Section is full (capacity: 1). Add student to waitlist.']);
    }

    public function test_promote_student_creates_new_academic_record(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/students', $this->enrolPayload());

        $student  = Student::where('admission_number', 'ADM-001')->first();
        $newYear  = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2027', 'is_current' => false, 'is_trash' => false]);
        $newClass = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6', 'weight' => 6, 'is_trash' => false]);
        $newSec   = Section::create(['school_id' => $this->school->id, 'class_id' => $newClass->id, 'name' => 'A', 'is_trash' => false]);

        auth()->forgetGuards();

        $this->withToken($this->token())
            ->postJson("/api/v2/students/{$student->id}/academics/promote", [
                'academic_year_id' => $newYear->id,
                'class_id'         => $newClass->id,
                'section_id'       => $newSec->id,
            ])
            ->assertOk();

        $this->assertEquals(2, $student->academics()->count());
        $this->assertEquals(1, $student->academics()->where('is_current', true)->count());
        $this->assertEquals($newClass->id, $student->currentAcademic()->value('class_id'));
    }

    public function test_transfer_student_changes_status_and_creates_tc(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/students', $this->enrolPayload());

        $student = Student::where('admission_number', 'ADM-001')->first();
        auth()->forgetGuards();

        $this->withToken($this->token())
            ->postJson("/api/v2/students/{$student->id}/transfer", ['reason' => 'transfer'])
            ->assertOk();

        $this->assertEquals('transferred', $student->fresh()->status);
        $this->assertDatabaseHas('transfer_certificates', ['student_id' => $student->id, 'reason' => 'transfer']);
    }

    public function test_re_admit_student(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/students', $this->enrolPayload());

        $student = Student::where('admission_number', 'ADM-001')->first();
        $student->update(['status' => 'transferred']);

        auth()->forgetGuards();

        $newYear = AcademicYear::create(['school_id' => $this->school->id, 'name' => '2027', 'is_current' => false, 'is_trash' => false]);

        $this->withToken($this->token())
            ->postJson("/api/v2/students/{$student->id}/re-admit", [
                'academic_year_id' => $newYear->id,
                'class_id'         => $this->class->id,
                'section_id'       => $this->section->id,
            ])
            ->assertOk();

        $this->assertEquals('active', $student->fresh()->status);
        $this->assertEquals(1, $student->fresh()->re_admission_count);
    }

    public function test_sibling_link_creates_bidirectional_records(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/students', $this->enrolPayload(['admission_number' => 'ADM-001']));

        $studentA = Student::where('admission_number', 'ADM-001')->first();
        auth()->forgetGuards();

        $this->withToken($this->token())
            ->postJson('/api/v2/students', $this->enrolPayload([
                'admission_number'       => 'ADM-002',
                'name'                   => 'Jane Doe',
                'sibling_admission_number' => 'ADM-001',
            ]));

        $studentB = Student::where('admission_number', 'ADM-002')->first();

        $this->assertDatabaseHas('student_siblings', ['student_id' => $studentA->id, 'sibling_id' => $studentB->id]);
        $this->assertDatabaseHas('student_siblings', ['student_id' => $studentB->id, 'sibling_id' => $studentA->id]);
    }

    public function test_waitlist_entry_created_with_position(): void
    {
        $response = $this->withToken($this->token())
            ->postJson('/api/v2/waitlist', [
                'academic_year_id' => $this->year->id,
                'class_id'         => $this->class->id,
                'section_id'       => $this->section->id,
                'applicant_name'   => 'Ali Hassan',
                'guardian_name'    => 'Hassan Ali',
                'guardian_phone'   => '01711111111',
            ])
            ->assertCreated();

        $this->assertEquals(1, $response->json('data.position'));

        auth()->forgetGuards();

        // Second entry gets position 2
        $response2 = $this->withToken($this->token())
            ->postJson('/api/v2/waitlist', [
                'academic_year_id' => $this->year->id,
                'class_id'         => $this->class->id,
                'section_id'       => $this->section->id,
                'applicant_name'   => 'Rina Begum',
                'guardian_name'    => 'Begum Ali',
                'guardian_phone'   => '01722222222',
            ])
            ->assertCreated();

        $this->assertEquals(2, $response2->json('data.position'));
    }

    public function test_admin_can_list_students(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/v2/students', $this->enrolPayload());

        auth()->forgetGuards();

        $this->withToken($this->token())
            ->getJson('/api/v2/students')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'student_id', 'status']]]);
    }
}
