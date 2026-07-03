<?php

namespace Tests\Feature\DataImport;

use App\Modules\Staff\Models\Department;
use App\Modules\Staff\Models\Designation;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\Storage;

class DataImportStaffTest extends DataImportTestCase
{
    private const HEADINGS = [
        'name', 'gender', 'dob', 'designation_name', 'department_name',
        'joining_date', 'employment_type', 'basic_salary',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');

        Designation::create(['school_id' => $this->school->id, 'name' => 'Teacher']);
        Department::create(['school_id' => $this->school->id, 'name' => 'Science']);
    }

    public function test_admin_can_import_staff_with_designation_and_department(): void
    {
        $file = $this->makeXlsxUpload(self::HEADINGS, [
            ['John Smith', 'male', '1990-01-20', 'Teacher', 'Science', '2026-07-01', 'permanent', '25000'],
        ]);

        $response = $this->withToken($this->adminToken())
            ->post('/api/v2/data-imports', ['type' => 'staff', 'file' => $file])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'completed', 'total_rows' => 1, 'success_count' => 1, 'skipped_count' => 0]);

        $this->assertSame([], $response->json('data.errors'));

        $staff = Staff::where('name', 'John Smith')->firstOrFail();
        $this->assertSame('Teacher', $staff->designation->name);
        $this->assertSame('Science', $staff->department->name);
        $this->assertSame('permanent', $staff->employment_type);
    }

    public function test_row_with_invalid_gender_is_skipped_and_reported(): void
    {
        $file = $this->makeXlsxUpload(self::HEADINGS, [
            ['Jane Doe', 'not-a-gender', '', '', '', '', '', ''],
        ]);

        $response = $this->withToken($this->adminToken())
            ->post('/api/v2/data-imports', ['type' => 'staff', 'file' => $file])
            ->assertCreated()
            ->assertJsonFragment(['total_rows' => 1, 'success_count' => 0, 'skipped_count' => 1]);

        $errors = $response->json('data.errors');
        $this->assertCount(1, $errors);
        $this->assertSame(2, $errors[0]['row']);

        $this->assertNull(Staff::where('name', 'Jane Doe')->first());
    }

    public function test_unknown_designation_is_skipped_and_reported(): void
    {
        $file = $this->makeXlsxUpload(self::HEADINGS, [
            ['Mia Lee', 'female', '', 'Head Librarian', '', '', '', ''],
        ]);

        $response = $this->withToken($this->adminToken())
            ->post('/api/v2/data-imports', ['type' => 'staff', 'file' => $file])
            ->assertCreated()
            ->assertJsonFragment(['success_count' => 0, 'skipped_count' => 1]);

        $this->assertStringContainsString("Designation 'Head Librarian' was not found.", $response->json('data.errors.0.messages.0'));
    }

    public function test_index_and_show_return_batch_history(): void
    {
        $file = $this->makeXlsxUpload(self::HEADINGS, [
            ['John Smith', 'male', '', '', '', '', '', ''],
        ]);
        $token = $this->adminToken();

        $created = $this->withToken($token)
            ->post('/api/v2/data-imports', ['type' => 'staff', 'file' => $file])
            ->assertCreated();

        $id = $created->json('data.id');

        $this->withToken($token)->getJson('/api/v2/data-imports')->assertOk()->assertJsonCount(1, 'data');
        $this->withToken($token)->getJson("/api/v2/data-imports/{$id}")->assertOk()
            ->assertJsonFragment(['status' => 'completed']);
    }

    public function test_template_download_works_for_both_types(): void
    {
        $token = $this->adminToken();

        $this->withToken($token)->get('/api/v2/data-imports/template?type=student')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->withToken($token)->get('/api/v2/data-imports/template?type=staff')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
