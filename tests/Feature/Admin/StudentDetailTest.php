<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAddress;
use App\Modules\Student\Models\StudentDocument;
use App\Modules\Student\Services\StudentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        $this->seed(RoleSeeder::class);

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
            ->assertSee('Enrolment History')
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

    private function withPhotoAndDocument(): StudentDocument
    {
        Storage::fake('minio');
        Storage::disk('minio')->put('students/1/photos/p.jpg', 'photo-bytes');
        Storage::disk('minio')->put('students/1/documents/d.pdf', 'doc-bytes');
        $this->student->update(['photo' => 'students/1/photos/p.jpg']);
        StudentAddress::create(['student_id' => $this->student->id, 'type' => 'present', 'address' => '123 School Road']);

        return StudentDocument::create([
            'school_id' => $this->school->id, 'student_id' => $this->student->id, 'document_type' => 'other',
            'file_path' => 'students/1/documents/d.pdf', 'original_name' => 'Birth Certificate Scan.pdf',
        ]);
    }

    public function test_detail_page_shows_photo_addresses_and_documents(): void
    {
        $document = $this->withPhotoAndDocument();

        $this->actingAs($this->admin)->get("/admin/students/{$this->student->id}")->assertOk()
            ->assertSee(route('admin.students.photo', $this->student->id), false)
            ->assertSee('123 School Road')
            ->assertSee('Birth Certificate Scan.pdf')
            ->assertSee(route('admin.students.documents.show', [$this->student->id, $document->id]), false);
    }

    public function test_admin_can_open_the_photo_and_download_a_document(): void
    {
        $document = $this->withPhotoAndDocument();
        $this->actingAs($this->admin);

        $photo = $this->get(route('admin.students.photo', $this->student->id))->assertOk();
        $this->assertSame('photo-bytes', $photo->streamedContent());

        $download = $this->get(route('admin.students.documents.show', [$this->student->id, $document->id]))->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('Content-Disposition'));
        $this->assertSame('doc-bytes', $download->streamedContent());
    }

    public function test_student_files_are_admin_only(): void
    {
        $document = $this->withPhotoAndDocument();
        $accountant = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $accountant->assignRole('accountant');

        $this->actingAs($accountant);
        $this->get(route('admin.students.photo', $this->student->id))->assertForbidden();
        $this->get(route('admin.students.documents.show', [$this->student->id, $document->id]))->assertForbidden();
    }
}
