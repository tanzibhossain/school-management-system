<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Services\StudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — People › Student detail page (tabs + transfer action).
 */
class StudentDetailTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private Student $student;

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

        $year = AcademicYear::create(['school_id' => $this->school->id, 'year' => 2026, 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        $section = Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A']);

        $this->student = app(StudentService::class)->enrol(
            $this->school->id,
            ['name' => 'Rahim Uddin', 'gender' => 'male', 'admission_number' => 'ADM-001'],
            ['academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id, 'roll_number' => '5'],
            [['name' => 'Karim Uddin', 'relation' => 'father', 'phone' => '01700000000', 'is_primary' => true]],
        );
    }

    public function test_detail_page_renders_tabs(): void
    {
        $this->actingAs($this->admin);

        $this->get("/admin/students/{$this->student->id}")
            ->assertOk()
            ->assertSee('Rahim Uddin')
            ->assertSee('Enrolment history')
            ->assertSee('Guardians')
            ->assertSee('Karim Uddin')
            ->assertSee('Class 6');
    }

    public function test_transfer_marks_student_transferred(): void
    {
        $this->actingAs($this->admin);

        $this->patch("/admin/students/{$this->student->id}/transfer", ['reason' => 'Moved city'])
            ->assertRedirect();

        $this->assertDatabaseHas('students', ['id' => $this->student->id, 'status' => 'transferred']);
    }
}
