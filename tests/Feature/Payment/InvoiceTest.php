<?php

namespace Tests\Feature\Payment;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\FeeItem\Models\FeeCategory;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\StudentCredit;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
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

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school  = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin   = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create([
            'school_id'  => $this->school->id,
            'year'       => '2026',
            'is_current' => true,
        ]);

        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 1']);

        $studentUser = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $this->student = Student::create([
            'school_id'        => $this->school->id,
            'user_id'          => $studentUser->id,
            'admission_number' => 'ADM-2026-001',
            'name'             => 'Test Student',
            'gender'           => 'male',
            'status'           => 'active',
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
            'school_id'        => $this->school->id,
            'category_id'      => $category->id,
            'academic_year_id' => $this->year->id,
            'class_id'         => null,  // school-wide; student has no academic record in tests
            'name'             => 'Tuition Fee',
            'amount'           => $amount,
            'frequency'        => 'monthly',
            'is_mandatory'     => true,
            'is_active'        => true,
        ]);
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    public function test_admin_can_generate_invoice_for_student(): void
    {
        $this->makeFeeItem();

        $this->withToken($this->token())
            ->postJson('/api/v2/invoices/generate', [
                'student_id'       => $this->student->id,
                'academic_year_id' => $this->year->id,
                'month'            => 1,
                'due_date'         => '2026-01-31',
            ])
            ->assertCreated()
            ->assertJsonFragment(['student_id' => $this->student->id]);

        $this->assertDatabaseHas('invoices', [
            'school_id'  => $this->school->id,
            'student_id' => $this->student->id,
            'month'      => 1,
            'status'     => 'unpaid',
        ]);
    }

    public function test_duplicate_invoice_returns_existing(): void
    {
        $this->makeFeeItem();
        $token = $this->token();

        $payload = [
            'student_id'       => $this->student->id,
            'academic_year_id' => $this->year->id,
            'month'            => 2,
            'due_date'         => '2026-02-28',
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
            'school_id'        => $this->school->id,
            'student_id'       => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number'   => 'INV-2026-00001',
            'credit_applied'   => 0,
            'amount_due'       => 5000,
            'status'           => 'unpaid',
            'due_date'         => '2026-01-31',
            'issued_by'        => $this->admin->id,
        ]);

        $this->withToken($this->token())
            ->getJson('/api/v2/invoices')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_admin_can_show_invoice(): void
    {
        $invoice = Invoice::create([
            'school_id'        => $this->school->id,
            'student_id'       => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number'   => 'INV-2026-00001',
            'credit_applied'   => 0,
            'amount_due'       => 5000,
            'status'           => 'unpaid',
            'due_date'         => '2026-01-31',
            'issued_by'        => $this->admin->id,
        ]);

        $this->withToken($this->token())
            ->getJson("/api/v2/invoices/{$invoice->id}")
            ->assertOk()
            ->assertJsonFragment(['invoice_number' => 'INV-2026-00001']);
    }

    public function test_admin_can_cancel_invoice(): void
    {
        $invoice = Invoice::create([
            'school_id'        => $this->school->id,
            'student_id'       => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number'   => 'INV-2026-00001',
            'credit_applied'   => 0,
            'amount_due'       => 5000,
            'status'           => 'unpaid',
            'due_date'         => '2026-01-31',
            'issued_by'        => $this->admin->id,
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
            'school_id'        => $this->school->id,
            'student_id'       => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number'   => 'INV-2026-00001',
            'credit_applied'   => 0,
            'amount_due'       => 5000,
            'status'           => 'unpaid',
            'due_date'         => '2026-01-31',
            'issued_by'        => $this->admin->id,
        ]);

        $this->withToken($this->token())
            ->postJson("/api/v2/invoices/{$invoice->id}/waive", ['note' => 'Scholarship waiver'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'waived']);
    }

    public function test_cannot_cancel_paid_invoice(): void
    {
        $invoice = Invoice::create([
            'school_id'        => $this->school->id,
            'student_id'       => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number'   => 'INV-2026-00001',
            'credit_applied'   => 0,
            'amount_paid'      => 5000,
            'amount_due'       => 5000,
            'status'           => 'paid',
            'due_date'         => '2026-01-31',
            'issued_by'        => $this->admin->id,
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
            'school_id'  => $this->school->id,
            'student_id' => $this->student->id,
            'balance'    => 1000,
        ]);

        $response = $this->withToken($this->token())
            ->postJson('/api/v2/invoices/generate', [
                'student_id'       => $this->student->id,
                'academic_year_id' => $this->year->id,
                'month'            => 3,
                'due_date'         => '2026-03-31',
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
            'school_id'        => $this->school->id,
            'student_id'       => $this->student->id,
            'academic_year_id' => $this->year->id,
            'invoice_number'   => 'INV-2026-00001',
            'credit_applied'   => 0,
            'amount_due'       => 5000,
            'status'           => 'unpaid',
            'due_date'         => '2026-01-31',
            'issued_by'        => $this->admin->id,
        ]);

        $token = $studentUser->createToken('test', ['student:*'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v2/my-invoices')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
