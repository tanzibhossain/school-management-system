<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Certificate\Models\AdmitCard;
use App\Modules\Certificate\Models\Testimonial;
use App\Modules\Certificate\Models\TestimonialTemplate;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamType;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Blade admin — Certificates (testimonial templates, testimonials, admit cards).
 */
class CertificateAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('minio');
        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    private function template(): TestimonialTemplate
    {
        return TestimonialTemplate::create([
            'school_id' => $this->school->id, 'name' => 'Standard',
            'template_body' => 'This is to certify that {{ student_name }}. {{ conduct_remark }}',
            'is_default' => true, 'signatory_name' => 'Head', 'signatory_designation' => 'Principal',
        ]);
    }

    private function student(): Student
    {
        return Student::create(['school_id' => $this->school->id, 'name' => 'Grad', 'gender' => 'male', 'admission_number' => 'ADM-1', 'status' => 'active']);
    }

    public function test_screens_load(): void
    {
        $this->actingAs($this->admin);
        foreach (['/admin/testimonials', '/admin/admit-cards', '/admin/cert-templates'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_template_crud(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/cert-templates', ['name' => 'T1', 'template_body' => 'Body {{ student_name }}', 'is_default' => 1])->assertRedirect();
        $tpl = TestimonialTemplate::where('school_id', $this->school->id)->firstOrFail();
        $this->assertDatabaseHas('testimonial_templates', ['id' => $tpl->id, 'is_default' => true]);

        // creating a second default flips the first
        $this->post('/admin/cert-templates', ['name' => 'T2', 'template_body' => 'Body2', 'is_default' => 1])->assertRedirect();
        $this->assertDatabaseHas('testimonial_templates', ['id' => $tpl->id, 'is_default' => false]);
    }

    public function test_issue_and_download_testimonial(): void
    {
        $this->actingAs($this->admin);
        $this->template();
        $student = $this->student();

        $this->post('/admin/testimonials', ['student_id' => $student->id, 'conduct_remark' => 'Excellent conduct.'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $t = Testimonial::where('school_id', $this->school->id)->firstOrFail();
        $this->assertEquals('issued', $t->status);
        $this->assertNotNull($t->file_path);

        $res = $this->get("/admin/testimonials/{$t->id}/download");
        $res->assertOk();
        $this->assertEquals('application/pdf', $res->headers->get('content-type'));
    }

    public function test_generate_and_download_admit_card(): void
    {
        $this->actingAs($this->admin);
        $student = $this->student();
        AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        $type = ExamType::create(['school_id' => $this->school->id, 'name' => 'Final', 'is_active' => true]);
        $exam = Exam::create([
            'school_id' => $this->school->id, 'exam_type_id' => $type->id, 'academic_year_id' => 1,
            'class_id' => $class->id, 'title' => 'Final 2026', 'start_date' => '2026-11-01', 'end_date' => '2026-11-10',
        ]);

        $this->post('/admin/admit-cards', ['student_id' => $student->id, 'exam_id' => $exam->id])
            ->assertSessionHasNoErrors()->assertRedirect();

        $card = AdmitCard::where('school_id', $this->school->id)->firstOrFail();
        Storage::disk('minio')->assertExists($card->file_path);

        $this->get("/admin/admit-cards/{$card->id}/download")->assertOk();
    }
}
