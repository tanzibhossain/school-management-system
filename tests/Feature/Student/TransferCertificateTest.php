<?php

namespace Tests\Feature\Student;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\TransferCertificateTemplate;
use App\Modules\Student\Services\TransferCertificateService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransferCertificateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('minio');

        $this->school = School::create(['name' => 'Test School', 'timezone' => 'UTC', 'is_active' => true]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $studentUser = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $studentUser->id,
            'admission_number' => 'ADM-001',
            'name' => 'Student One',
            'gender' => 'male',
            'status' => 'active',
        ]);

        TransferCertificateTemplate::create([
            'school_id' => $this->school->id,
            'name' => 'Default TC',
            'template_body' => '<p>{{student_name}} — {{tc_number}} — {{reason}}</p>',
            'is_default' => true,
        ]);
    }

    private function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    public function test_issuing_a_transfer_certificate_now_generates_and_stores_a_real_pdf(): void
    {
        $token = $this->adminToken();

        // Student::transfer() (StudentController) is what normally creates the draft TC;
        // exercise the service directly here to isolate the issue()/PDF behavior being tested.
        $tc = app(TransferCertificateService::class)
            ->generate($this->student, 'transfer', null, $this->admin);

        $this->withToken($token)
            ->postJson("/api/v2/students/tcs/{$tc->id}/issue")
            ->assertOk()
            ->assertJsonFragment(['status' => 'issued']);

        $tc = $tc->fresh();
        $this->assertNotNull($tc->file_path);
        Storage::disk('minio')->assertExists($tc->file_path);
        $this->assertStringStartsWith('%PDF', Storage::disk('minio')->get($tc->file_path));
    }
}
