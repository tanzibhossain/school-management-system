<?php

namespace Tests\Feature\Payment;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private AcademicYear $year;
    private SchoolClass $class;
    private Student $student;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin  = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
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

        $this->invoice = Invoice::create([
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
    }

    private function token(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    public function test_admin_can_record_cash_payment(): void
    {
        $this->withToken($this->token())
            ->postJson("/api/v2/payments/invoices/{$this->invoice->id}/record", [
                'method' => 'cash',
                'amount'         => 5000,
            ])
            ->assertCreated()
            ->assertJsonFragment(['method' => 'cash']);

        $this->assertDatabaseHas('invoices', [
            'id'     => $this->invoice->id,
            'status' => 'paid',
        ]);
    }

    public function test_admin_can_record_cheque_payment(): void
    {
        $this->withToken($this->token())
            ->postJson("/api/v2/payments/invoices/{$this->invoice->id}/record", [
                'method' => 'cheque',
                'amount'         => 5000,
                'cheque_number'  => 'CHQ-001',
                'bank_name'      => 'Dhaka Bank',
                'cheque_date'    => '2026-01-25',
            ])
            ->assertCreated()
            ->assertJsonFragment(['cheque_status' => 'submitted']);

        $this->assertDatabaseHas('payments', [
            'cheque_number' => 'CHQ-001',
            'cheque_status' => 'submitted',
        ]);
    }

    public function test_partial_payment_sets_invoice_partial(): void
    {
        $this->withToken($this->token())
            ->postJson("/api/v2/payments/invoices/{$this->invoice->id}/record", [
                'method' => 'cash',
                'amount'         => 2000,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('invoices', [
            'id'     => $this->invoice->id,
            'status' => 'partial',
        ]);
    }

    public function test_overpayment_credits_excess_to_student(): void
    {
        $this->withToken($this->token())
            ->postJson("/api/v2/payments/invoices/{$this->invoice->id}/record", [
                'method' => 'cash',
                'amount'         => 6000, // 1000 overpayment
            ])
            ->assertCreated();

        $this->assertDatabaseHas('invoices', ['id' => $this->invoice->id, 'status' => 'paid']);
        $this->assertDatabaseHas('student_credits', [
            'student_id' => $this->student->id,
            'balance'    => 1000,
        ]);
    }

    public function test_admin_can_view_payment(): void
    {
        $payment = Payment::create([
            'school_id'      => $this->school->id,
            'invoice_id'     => $this->invoice->id,
            'student_id'     => $this->student->id,
            'receipt_number' => 'REC-2026-00001',
            'method' => 'cash',
            'amount'         => 5000,
            'collected_by'   => $this->admin->id,
            'paid_at'        => now(),
        ]);

        $this->withToken($this->token())
            ->getJson("/api/v2/payments/{$payment->id}")
            ->assertOk()
            ->assertJsonFragment(['receipt_number' => 'REC-2026-00001']);
    }

    public function test_admin_can_clear_cheque(): void
    {
        $payment = Payment::create([
            'school_id'      => $this->school->id,
            'invoice_id'     => $this->invoice->id,
            'student_id'     => $this->student->id,
            'receipt_number' => 'REC-2026-00001',
            'method' => 'cheque',
            'amount'         => 5000,
            'cheque_number'  => 'CHQ-001',
            'bank_name'      => 'Dhaka Bank',
            'cheque_date'    => '2026-01-25',
            'cheque_status'  => 'submitted',
            'collected_by'   => $this->admin->id,
            'paid_at'        => now(),
        ]);

        // Mark invoice as paid for this test
        $this->invoice->update(['status' => 'paid', 'amount_due' => 0]);

        $this->withToken($this->token())
            ->postJson("/api/v2/cheques/{$payment->id}/clear")
            ->assertOk()
            ->assertJsonFragment(['cheque_status' => 'cleared']);
    }

    public function test_admin_can_bounce_cheque(): void
    {
        $payment = Payment::create([
            'school_id'      => $this->school->id,
            'invoice_id'     => $this->invoice->id,
            'student_id'     => $this->student->id,
            'receipt_number' => 'REC-2026-00001',
            'method' => 'cheque',
            'amount'         => 5000,
            'cheque_number'  => 'CHQ-001',
            'bank_name'      => 'Dhaka Bank',
            'cheque_date'    => '2026-01-25',
            'cheque_status'  => 'submitted',
            'collected_by'   => $this->admin->id,
            'paid_at'        => now(),
        ]);

        // Invoice was partially or fully paid before cheque submitted
        $this->invoice->update(['status' => 'paid', 'amount_due' => 0]);

        $this->withToken($this->token())
            ->postJson("/api/v2/cheques/{$payment->id}/bounce", [
                'bounce_fee' => 500,
            ])
            ->assertOk()
            ->assertJsonFragment(['is_reversed' => true]);

        // Invoice should be reopened with bounce fee added
        $this->assertDatabaseHas('invoices', [
            'id'     => $this->invoice->id,
            'status' => 'unpaid',
        ]);
    }

    public function test_cannot_record_payment_on_cancelled_invoice(): void
    {
        $this->invoice->update(['status' => 'cancelled']);

        $this->withToken($this->token())
            ->postJson("/api/v2/payments/invoices/{$this->invoice->id}/record", [
                'method' => 'cash',
                'amount'         => 5000,
            ])
            ->assertStatus(422);
    }
}
