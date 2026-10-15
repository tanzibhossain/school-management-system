<?php

namespace Tests\Feature\Payment;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\FeeItem\Models\FeeCategory;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\StudentCredit;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $year;

    private SchoolClass $class;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'year' => '2026',
            'is_current' => true,
        ]);

        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 1']);

        $studentUser = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $this->student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $studentUser->id,
            'admission_number' => 'ADM-2026-001',
            'name' => 'Test Student',
            'gender' => 'male',
            'status' => 'active',
        ]);
    }

    private function token(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    private function makeFeeItem(float $amount = 5000): FeeItem
    {
        $category = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Academic']);

        return FeeItem::create([
            'school_id' => $this->school->id,
            'category_id' => $category->id,
            'academic_year_id' => $this->year->id,
            'class_id' => null,  // school-wide; student has no academic record in tests
            'name' => 'Tuition Fee',
            'amount' => $amount,
            'frequency' => 'monthly',
            'is_mandatory' => true,
            'is_active' => true,
        ]);
    }

    /** Put a student in a class for this year (class membership lives on student_academics). */
    private function placeInClass(Student $student, SchoolClass $class): void
    {
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $class->id,
            'section_id' => Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'S'.$student->id])->id,
            'is_current' => true,
        ]);
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    // InvoiceService::generateBulk() used to query students.class_id, a column
    // that doesn't exist, so this API path returned 500 on every call.
    public function test_admin_can_bulk_generate_invoices_for_a_class(): void
    {
        $this->makeFeeItem();
        $classmate = Student::create([
            'school_id' => $this->school->id, 'admission_number' => 'ADM-2026-002',
            'name' => 'Classmate', 'gender' => 'female', 'status' => 'active',
        ]);
        $otherClassStudent = Student::create([
            'school_id' => $this->school->id, 'admission_number' => 'ADM-2026-003',
            'name' => 'Other Class', 'gender' => 'male', 'status' => 'active',
        ]);
        $this->placeInClass($this->student, $this->class);
        $this->placeInClass($classmate, $this->class);
        $this->placeInClass($otherClassStudent, SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 2']));

        $payload = [
            'class_id' => $this->class->id,
            'academic_year_id' => $this->year->id,
            'month' => 3,
            'due_date' => '2026-03-31',
        ];

        $this->withToken($this->token())->postJson('/api/v2/invoices/generate', $payload)
            ->assertOk()
            ->assertJson(['generated' => 2, 'skipped' => 0, 'failed' => 0]);

        $this->assertSame(2, Invoice::where('school_id', $this->school->id)->where('month', 3)->count());
        $this->assertDatabaseMissing('invoices', ['student_id' => $otherClassStudent->id]);

        // Running it again skips both (each already has an open invoice for the period).
        auth()->forgetGuards();
        $this->withToken($this->token())->postJson('/api/v2/invoices/generate', $payload)
            ->assertOk()
            ->assertJson(['generated' => 0, 'skipped' => 2]);
    }

    public function test_admin_can_generate_invoice_for_student(): void
    {
        $this->makeFeeItem();

        $this->withToken($this->token())
            ->postJson('/api/v2/invoices/generate', [
                'student_id' => $this->student->id,
                'academic_year_id' => $this->year->id,
                'month' => 1,
                'due_date' => '2026-01-31',
            ])
            ->assertCreated()
            ->assertJsonFragment(['student_id' => $this->student->id]);

        $this->assertDatabaseHas('invoices', [
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'month' => 1,
            'status' => 'unpaid',
        ]);
    }

    public function test_duplicate_invoice_returns_existing(): void
    {
        $this->makeFeeItem();
        $token = $this->token();

        $payload = [
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'month' => 2,
            'due_date' => '2026-02-28',
        ];

        $this->withToken($token)->postJson('/api/v2/invoices/generate', $payload)->assertCreated();
        // Second call — should return existing (201 or 200, same invoice_number)
        $second = $this->withToken($token)->postJson('/api/v2/invoices/generate', $payload);
        $second->assertSuccessful();

        $this->assertDatabaseCount('invoices', 1);
    }

    public function test_admin_can_list_invoices(): void
    {
        Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number' => 'INV-2026-00001',
            'credit_applied' => 0,
            'amount_due' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-01-31',
            'issued_by' => $this->admin->id,
        ]);

        $this->withToken($this->token())
            ->getJson('/api/v2/invoices')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_show_invoice(): void
    {
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number' => 'INV-2026-00001',
            'credit_applied' => 0,
            'amount_due' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-01-31',
            'issued_by' => $this->admin->id,
        ]);

        $this->withToken($this->token())
            ->getJson("/api/v2/invoices/{$invoice->id}")
            ->assertOk()
            ->assertJsonFragment(['invoice_number' => 'INV-2026-00001']);
    }

    public function test_admin_can_cancel_invoice(): void
    {
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number' => 'INV-2026-00001',
            'credit_applied' => 0,
            'amount_due' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-01-31',
            'issued_by' => $this->admin->id,
        ]);

        $this->withToken($this->token())
            ->postJson("/api/v2/invoices/{$invoice->id}/cancel", ['note' => 'Student transferred'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'cancelled']);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'cancelled']);
    }

    public function test_admin_can_waive_invoice(): void
    {
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number' => 'INV-2026-00001',
            'credit_applied' => 0,
            'amount_due' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-01-31',
            'issued_by' => $this->admin->id,
        ]);

        $this->withToken($this->token())
            ->postJson("/api/v2/invoices/{$invoice->id}/waive", ['note' => 'Scholarship waiver'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'waived']);
    }

    public function test_cannot_cancel_paid_invoice(): void
    {
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number' => 'INV-2026-00001',
            'credit_applied' => 0,
            'amount_paid' => 5000,
            'amount_due' => 5000,
            'status' => 'paid',
            'due_date' => '2026-01-31',
            'issued_by' => $this->admin->id,
        ]);

        $this->withToken($this->token())
            ->postJson("/api/v2/invoices/{$invoice->id}/cancel", ['note' => 'Error'])
            ->assertStatus(422);
    }

    public function test_invoice_applies_credit_balance(): void
    {
        $this->makeFeeItem(5000);

        // Give student 1000 credit
        StudentCredit::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'balance' => 1000,
        ]);

        $response = $this->withToken($this->token())
            ->postJson('/api/v2/invoices/generate', [
                'student_id' => $this->student->id,
                'academic_year_id' => $this->year->id,
                'month' => 3,
                'due_date' => '2026-03-31',
            ])
            ->assertCreated();

        // amount_due should be 4000 after 1000 credit applied
        $this->assertEquals(4000, $response->json('data.amount_due'));
    }

    public function test_student_can_view_own_invoices(): void
    {
        $studentUser = $this->student->user()->firstOrFail();
        $studentUser->assignRole('student');

        Invoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number' => 'INV-2026-00001',
            'credit_applied' => 0,
            'amount_due' => 5000,
            'status' => 'unpaid',
            'due_date' => '2026-01-31',
            'issued_by' => $this->admin->id,
        ]);

        $token = $studentUser->createToken('test', ['student:*'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v2/my-invoices')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
