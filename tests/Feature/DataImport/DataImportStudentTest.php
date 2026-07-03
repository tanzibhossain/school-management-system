<?php

namespace Tests\Feature\DataImport;

use App\Modules\Student\Models\Student;
use Illuminate\Support\Facades\Storage;

class DataImportStudentTest extends DataImportTestCase
{
    private const HEADINGS = [
        'admission_number', 'name', 'gender', 'dob', 'blood_group',
        'class_name', 'section_name', 'academic_year', 'roll_number',
        'guardian_name', 'guardian_phone', 'guardian_relation',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
    }

    public function test_admin_can_import_students_with_a_guardian(): void
    {
        $file = $this->makeXlsxUpload(self::HEADINGS, [
            ['ADM-1', 'Alice', 'female', '2012-01-01', 'O+', 'Class 5', 'A', '2026', '1', 'Bob Guardian', '+8801700000001', 'father'],
            ['ADM-2', 'Carol', 'female', '2012-02-02', 'A+', 'Class 5', 'A', '2026', '2', '', '', ''],
        ]);

        $response = $this->withToken($this->adminToken())
            ->post('/api/v2/data-imports', ['type' => 'student', 'file' => $file])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'completed', 'total_rows' => 2, 'success_count' => 2, 'skipped_count' => 0]);

        $this->assertSame([], $response->json('data.errors'));

        $alice = Student::where('admission_number', 'ADM-1')->firstOrFail();
        $this->assertSame('Class 5', $alice->currentAcademic->schoolClass->name);
        $this->assertSame('A', $alice->currentAcademic->section->name);
        $this->assertSame('1', $alice->currentAcademic->roll_number);
        $this->assertCount(1, $alice->guardians);
        $this->assertSame('Bob Guardian', $alice->guardians->first()->name);
        $this->assertSame('father', $alice->guardians->first()->relation);

        $carol = Student::where('admission_number', 'ADM-2')->firstOrFail();
        $this->assertCount(0, $carol->guardians);
    }

    public function test_row_with_unknown_class_is_skipped_and_reported(): void
    {
        $file = $this->makeXlsxUpload(self::HEADINGS, [
            ['ADM-3', 'Dan', 'male', '', '', 'Nonexistent Class', 'A', '2026', '', '', '', ''],
        ]);

        $response = $this->withToken($this->adminToken())
            ->post('/api/v2/data-imports', ['type' => 'student', 'file' => $file])
            ->assertCreated()
            ->assertJsonFragment(['total_rows' => 1, 'success_count' => 0, 'skipped_count' => 1]);

        $errors = $response->json('data.errors');
        $this->assertCount(1, $errors);
        $this->assertSame(2, $errors[0]['row']);
        $this->assertStringContainsString("Class 'Nonexistent Class' was not found.", $errors[0]['messages'][0]);

        $this->assertNull(Student::where('admission_number', 'ADM-3')->first());
    }

    public function test_duplicate_admission_number_within_the_same_file_is_skipped(): void
    {
        $file = $this->makeXlsxUpload(self::HEADINGS, [
            ['ADM-6', 'Eve', 'female', '', '', 'Class 5', 'A', '2026', '', '', '', ''],
            ['ADM-6', 'Eve Duplicate', 'female', '', '', 'Class 5', 'A', '2026', '', '', '', ''],
        ]);

        $response = $this->withToken($this->adminToken())
            ->post('/api/v2/data-imports', ['type' => 'student', 'file' => $file])
            ->assertCreated()
            ->assertJsonFragment(['success_count' => 1, 'skipped_count' => 1]);

        $errors = $response->json('data.errors');
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('already exists', $errors[0]['messages'][0]);

        $this->assertSame(1, Student::where('admission_number', 'ADM-6')->count());
    }

    public function test_teacher_cannot_import_students(): void
    {
        $file = $this->makeXlsxUpload(self::HEADINGS, [
            ['ADM-7', 'Frank', 'male', '', '', 'Class 5', 'A', '2026', '', '', '', ''],
        ]);

        $this->withToken($this->teacherToken())
            ->post('/api/v2/data-imports', ['type' => 'student', 'file' => $file])
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v2/data-imports', ['type' => 'student'])->assertUnauthorized();
    }
}
