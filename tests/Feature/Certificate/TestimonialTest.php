<?php

namespace Tests\Feature\Certificate;

use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Certificate\Models\Testimonial;
use App\Modules\Certificate\Models\TestimonialTemplate;
use App\Modules\Mark\Models\ExamResult;
use Illuminate\Support\Facades\Storage;

class TestimonialTest extends CertificateTestCase
{
    private TestimonialTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');

        $this->template = TestimonialTemplate::create([
            'school_id' => $this->school->id,
            'name' => 'Default',
            'template_body' => '<p>{{student_name}} ({{admission_number}}) — {{conduct_remark}}. '
                .'Grade: {{grade}}, GPA: {{gpa}}, Attendance: {{attendance_percentage}}.</p>',
            'is_default' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'conduct_remark' => 'Sincere and disciplined throughout the academic year.',
        ], $overrides);
    }

    public function test_generate_creates_a_draft_without_a_pdf(): void
    {
        $this->withToken($this->adminToken())
            ->postJson("/api/v2/certificates/testimonials/{$this->student->id}", $this->payload())
            ->assertCreated()
            ->assertJsonFragment(['status' => 'draft', 'file_url' => null]);

        $this->assertDatabaseCount('testimonials', 1);
    }

    public function test_issue_renders_the_pdf_and_marks_it_issued(): void
    {
        $response = $this->withToken($this->adminToken())
            ->postJson("/api/v2/certificates/testimonials/{$this->student->id}", $this->payload())
            ->assertCreated();

        $id = $response->json('data.id');

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/certificates/testimonials/{$id}/issue")
            ->assertOk()
            ->assertJsonFragment(['status' => 'issued']);

        $testimonial = Testimonial::findOrFail($id);
        $this->assertNotNull($testimonial->file_path);
        Storage::disk('minio')->assertExists($testimonial->file_path);
        $this->assertStringStartsWith('%PDF', Storage::disk('minio')->get($testimonial->file_path));
    }

    public function test_preview_substitutes_academic_and_attendance_placeholders(): void
    {
        // Backdate the student so the requested attendance window isn't clamped by enrollment
        $this->student->created_at = '2026-01-01 00:00:00';
        $this->student->save();

        ExamResult::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'total_marks' => 85,
            'total_possible' => 100,
            'percentage' => 85.00,
            'grade' => 'A+',
            'gpa' => 5.00,
            'is_pass' => true,
        ]);

        StudentAttendance::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->year->id,
            'date' => '2026-01-05',
            'status' => 'present',
            'recorded_by' => $this->admin->id,
        ]);

        $response = $this->withToken($this->adminToken())
            ->postJson("/api/v2/certificates/testimonials/{$this->student->id}", $this->payload([
                'exam_id' => $this->exam->id,
                'attendance_from' => '2026-01-05',
                'attendance_to' => '2026-01-05',
            ]))
            ->assertCreated();

        $id = $response->json('data.id');

        $html = $this->withToken($this->adminToken())
            ->getJson("/api/v2/certificates/testimonials/{$id}/preview")
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('Grade: A+', $html);
        $this->assertStringContainsString('GPA: 5.00', $html);
        $this->assertStringContainsString('Attendance: 100%', $html);
        $this->assertStringContainsString('Sincere and disciplined', $html);
    }

    public function test_preview_shows_na_when_no_exam_or_attendance_range_given(): void
    {
        $response = $this->withToken($this->adminToken())
            ->postJson("/api/v2/certificates/testimonials/{$this->student->id}", $this->payload())
            ->assertCreated();

        $id = $response->json('data.id');

        $html = $this->withToken($this->adminToken())
            ->getJson("/api/v2/certificates/testimonials/{$id}/preview")
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('Grade: N/A', $html);
        $this->assertStringContainsString('Attendance: N/A', $html);
    }

    public function test_non_admin_cannot_generate(): void
    {
        $this->withToken($this->teacherToken())
            ->postJson("/api/v2/certificates/testimonials/{$this->student->id}", $this->payload())
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->postJson("/api/v2/certificates/testimonials/{$this->student->id}", $this->payload())
            ->assertUnauthorized();
    }

    // ── Templates ────────────────────────────────────────────────────────────

    public function test_admin_can_manage_templates(): void
    {
        $this->withToken($this->adminToken())
            ->getJson('/api/v2/certificates/testimonial-templates')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/certificates/testimonial-templates', [
                'name' => 'Alt template',
                'template_body' => '<p>{{student_name}}</p>',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('testimonial_templates', 2);
    }
}
