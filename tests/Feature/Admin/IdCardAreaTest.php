<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\IdCard\Models\IdCardBatch;
use App\Modules\IdCard\Models\IdCardTemplate;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Blade admin — ID card templates + batch generation (queued job, sync in tests).
 */
class IdCardAreaTest extends TestCase
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
        Storage::fake('minio');
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

    private function enrol(string $adm): void
    {
        $student = Student::create(['school_id' => $this->school->id, 'name' => 'S' . $adm, 'gender' => 'male', 'admission_number' => $adm, 'status' => 'active']);
        StudentAcademic::create([
            'school_id' => $this->school->id, 'student_id' => $student->id, 'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id, 'section_id' => $this->section->id, 'is_current' => true,
        ]);
    }

    private function template(): IdCardTemplate
    {
        return IdCardTemplate::create([
            'school_id' => $this->school->id, 'type' => 'student', 'name' => 'Std', 'layout' => 'horizontal_classic',
            'font' => 'sans', 'visible_fields' => ['name', 'identifier', 'photo'], 'is_default' => true,
        ]);
    }

    public function test_screens_load(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/id-cards')->assertOk();
        $this->get('/admin/id-card-templates')->assertOk();
    }

    public function test_template_crud(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/id-card-templates', [
            'type' => 'student', 'name' => 'Blue', 'layout' => 'vertical', 'font' => 'sans',
            'visible_fields' => ['name', 'identifier'], 'is_default' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('id_card_templates', ['school_id' => $this->school->id, 'name' => 'Blue', 'layout' => 'vertical']);
    }

    public function test_generate_class_batch(): void
    {
        $this->actingAs($this->admin);
        $template = $this->template();
        $this->enrol('A1');
        $this->enrol('A2');
        $this->enrol('A3');

        $this->post('/admin/id-cards', [
            'type' => 'student', 'template_id' => $template->id, 'scope' => 'class', 'class_id' => $this->class->id,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $batch = IdCardBatch::where('school_id', $this->school->id)->firstOrFail();
        $this->assertEquals(3, $batch->total_count);
        $this->assertEquals('completed', $batch->status);
        $this->assertEquals(3, $batch->files()->first()->card_count);

        $this->get("/admin/id-cards/{$batch->id}/files/{$batch->files()->first()->id}/download")->assertOk();
    }
}
