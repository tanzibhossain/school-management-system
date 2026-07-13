<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\DataImport\Models\ImportBatch;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Blade admin — People › Data import (Excel upload → queued row import).
 */
class DataImportAreaTest extends TestCase
{
    use RefreshDatabase;

    private const HEADINGS = [
        'admission_number', 'name', 'gender', 'dob', 'blood_group',
        'class_name', 'section_name', 'academic_year', 'roll_number',
        'guardian_name', 'guardian_phone', 'guardian_relation',
    ];

    private School $school;

    private User $admin;

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

        AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A']);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function upload(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(self::HEADINGS, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');
        $path = tempnam(sys_get_temp_dir(), 'imp_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_screen_loads(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/data-import')->assertOk();
    }

    public function test_import_valid_student_row(): void
    {
        $this->actingAs($this->admin);

        $file = $this->upload([
            ['ADM-1', 'Imported Kid', 'male', '2015-01-01', 'A+', 'Class 6', 'A', '2026', '1', 'Parent', '01700000000', 'father'],
        ]);

        $this->post('/admin/data-import', ['type' => 'student', 'file' => $file])->assertRedirect();

        $batch = ImportBatch::where('school_id', $this->school->id)->firstOrFail();
        $this->assertEquals('completed', $batch->status);
        $this->assertEquals(1, $batch->success_count);
        $this->assertDatabaseHas('students', ['school_id' => $this->school->id, 'admission_number' => 'ADM-1', 'name' => 'Imported Kid']);
    }

    public function test_invalid_row_is_skipped_and_reported(): void
    {
        $this->actingAs($this->admin);

        $file = $this->upload([
            ['ADM-2', 'Bad Row', 'not-a-gender', '', '', 'Class 6', 'A', '2026', '', 'P', '01700000000', 'father'],
        ]);

        $this->post('/admin/data-import', ['type' => 'student', 'file' => $file])->assertRedirect();

        $batch = ImportBatch::where('school_id', $this->school->id)->firstOrFail();
        $this->assertEquals(1, $batch->skipped_count);
        $this->assertNotEmpty($batch->errors);
        $this->assertDatabaseMissing('students', ['admission_number' => 'ADM-2']);
    }

    public function test_rejects_non_spreadsheet_file(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/data-import', ['type' => 'student', 'file' => UploadedFile::fake()->create('notes.txt', 5, 'text/plain')])
            ->assertSessionHasErrors('file');
    }
}
