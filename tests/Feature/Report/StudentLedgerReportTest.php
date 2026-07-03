<?php

namespace Tests\Feature\Report;

use App\Modules\Payment\Models\CreditTransaction;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\Refund;
use App\Modules\Payment\Models\StudentCredit;

class StudentLedgerReportTest extends ReportTestCase
{
    public function test_ledger_combines_invoices_payments_and_credits(): void
    {
        $student = $this->makeStudent();

        Invoice::create([
            'school_id' => $this->school->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'amount_due' => 200,
            'currency' => 'USD',
            'amount_paid' => 100,
            'status' => 'partial',
            'due_date' => '2026-07-01',
            'issued_by' => $this->admin->id,
        ]);

        $invoice = Invoice::first();
        Payment::create([
            'school_id' => $this->school->id,
            'receipt_number' => $this->nextReceiptNumber(),
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'amount' => 100,
            'currency' => 'USD',
            'method' => 'cash',
            'is_reversed' => false,
            'collected_by' => $this->admin->id,
            'paid_at' => '2026-07-01 10:00:00',
        ]);

        CreditTransaction::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'type' => 'credit',
            'amount' => 50,
            'note' => 'Sibling discount',
            'created_by' => $this->admin->id,
        ]);

        StudentCredit::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'balance' => 50,
        ]);

        $this->withToken($this->adminToken())
            ->getJson("/api/v2/reports/students/{$student->id}/ledger")
            ->assertOk()
            ->assertJsonCount(3, 'data.entries')
            ->assertJsonFragment([
                'total_invoiced' => 200.0,
                'total_paid' => 100.0,
                'current_outstanding' => 100.0,
                'credit_balance' => 50.0,
            ]);
    }

    public function test_date_range_filters_entries(): void
    {
        $student = $this->makeStudent();

        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'amount_due' => 100,
            'currency' => 'USD',
            'amount_paid' => 100,
            'status' => 'paid',
            'due_date' => '2026-06-01',
            'issued_by' => $this->admin->id,
        ]);
        // created_at isn't mass-assignable and defaults to "now" (the actual test-run
        // time, i.e. today's env date) — force it back into June so the ledger's
        // date filter has something outside the queried July range to exclude.
        $invoice->created_at = '2026-06-01 10:00:00';
        $invoice->save();

        Payment::create([
            'school_id' => $this->school->id,
            'receipt_number' => $this->nextReceiptNumber(),
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'amount' => 100,
            'currency' => 'USD',
            'method' => 'cash',
            'is_reversed' => false,
            'collected_by' => $this->admin->id,
            'paid_at' => '2026-06-01 10:00:00',
        ]);

        $this->withToken($this->adminToken())
            ->getJson("/api/v2/reports/students/{$student->id}/ledger?date_from=2026-07-01&date_to=2026-07-31")
            ->assertOk()
            ->assertJsonCount(0, 'data.entries');
    }

    public function test_completed_refund_appears_in_the_ledger(): void
    {
        $student = $this->makeStudent();

        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'amount_due' => 100,
            'currency' => 'USD',
            'amount_paid' => 100,
            'status' => 'paid',
            'due_date' => '2026-07-01',
            'issued_by' => $this->admin->id,
        ]);

        $payment = Payment::create([
            'school_id' => $this->school->id,
            'receipt_number' => $this->nextReceiptNumber(),
            'invoice_id' => $invoice->id,
            'student_id' => $student->id,
            'amount' => 100,
            'currency' => 'USD',
            'method' => 'cash',
            'is_reversed' => false,
            'collected_by' => $this->admin->id,
            'paid_at' => '2026-07-01 10:00:00',
        ]);

        Refund::create([
            'school_id' => $this->school->id,
            'payment_id' => $payment->id,
            'amount' => 100,
            'processing_fee' => 5,
            'net_refund' => 95,
            'method' => 'cash',
            'status' => 'completed',
            'requested_by' => $this->admin->id,
            'processed_by' => $this->admin->id,
            'processed_at' => '2026-07-02 10:00:00',
        ]);

        $this->withToken($this->adminToken())
            ->getJson("/api/v2/reports/students/{$student->id}/ledger")
            ->assertOk()
            ->assertJsonFragment(['total_refunded' => 95.0])
            ->assertJsonFragment(['type' => 'refund']);
    }

    public function test_pdf_export_renders_a_real_pdf(): void
    {
        $student = $this->makeStudent();

        $response = $this->withToken($this->adminToken())
            ->get("/api/v2/reports/students/{$student->id}/ledger?format=pdf")
            ->assertOk();

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_teacher_cannot_view(): void
    {
        $student = $this->makeStudent();

        $this->withToken($this->teacherToken())
            ->getJson("/api/v2/reports/students/{$student->id}/ledger")
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $student = $this->makeStudent();

        $this->getJson("/api/v2/reports/students/{$student->id}/ledger")->assertUnauthorized();
    }
}
