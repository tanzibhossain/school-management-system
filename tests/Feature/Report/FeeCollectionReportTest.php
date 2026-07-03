<?php

namespace Tests\Feature\Report;

use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;

class FeeCollectionReportTest extends ReportTestCase
{
    private function payInvoice(int $studentId, float $amount, string $method = 'cash', ?string $paidAt = null): Payment
    {
        $invoice = Invoice::create([
            'school_id' => $this->school->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'student_id' => $studentId,
            'academic_year_id' => $this->year->id,
            'amount_due' => $amount,
            'currency' => 'USD',
            'amount_paid' => $amount,
            'status' => 'paid',
            'due_date' => '2026-07-01',
            'issued_by' => $this->admin->id,
        ]);

        return Payment::create([
            'school_id' => $this->school->id,
            'receipt_number' => $this->nextReceiptNumber(),
            'invoice_id' => $invoice->id,
            'student_id' => $studentId,
            'amount' => $amount,
            'currency' => 'USD',
            'method' => $method,
            'is_reversed' => false,
            'collected_by' => $this->admin->id,
            'paid_at' => $paidAt ?? '2026-07-01 10:00:00',
        ]);
    }

    public function test_admin_can_view_fee_collection_for_a_date_range(): void
    {
        $student = $this->makeStudent();
        $this->payInvoice($student->id, 500);

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/reports/fee-collection?date_from=2026-07-01&date_to=2026-07-01')
            ->assertOk()
            ->assertJsonFragment(['count' => 1])
            ->assertJsonFragment(['totals_by_currency' => ['USD' => 500.0]])
            ->assertJsonCount(1, 'data.payments');
    }

    public function test_payments_outside_the_date_range_are_excluded(): void
    {
        $student = $this->makeStudent();
        $this->payInvoice($student->id, 500, paidAt: '2026-06-01 10:00:00');

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/reports/fee-collection?date_from=2026-07-01&date_to=2026-07-31')
            ->assertOk()
            ->assertJsonFragment(['count' => 0]);
    }

    public function test_class_filter_scopes_results(): void
    {
        $otherClass = \App\Modules\Academic\Models\SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 9']);
        $otherSection = \App\Modules\Academic\Models\Section::create(['school_id' => $this->school->id, 'class_id' => $otherClass->id, 'name' => 'A']);

        $inClass = $this->makeStudent();
        $outOfClass = $this->makeStudent($otherClass->id, $otherSection->id);

        $this->payInvoice($inClass->id, 200);
        $this->payInvoice($outOfClass->id, 300);

        $this->withToken($this->adminToken())
            ->getJson("/api/v2/reports/fee-collection?date_from=2026-07-01&date_to=2026-07-01&class_id={$this->class->id}")
            ->assertOk()
            ->assertJsonFragment(['count' => 1])
            ->assertJsonFragment(['totals_by_currency' => ['USD' => 200.0]]);
    }

    public function test_reversed_payments_are_excluded(): void
    {
        $student = $this->makeStudent();
        $payment = $this->payInvoice($student->id, 500);
        $payment->update(['is_reversed' => true]);

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/reports/fee-collection?date_from=2026-07-01&date_to=2026-07-01')
            ->assertOk()
            ->assertJsonFragment(['count' => 0]);
    }

    public function test_pdf_export_renders_a_real_pdf(): void
    {
        $student = $this->makeStudent();
        $this->payInvoice($student->id, 500);

        $response = $this->withToken($this->adminToken())
            ->get('/api/v2/reports/fee-collection?date_from=2026-07-01&date_to=2026-07-01&format=pdf')
            ->assertOk();

        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_accountant_can_view_but_teacher_cannot(): void
    {
        $this->withToken($this->accountantToken())
            ->getJson('/api/v2/reports/fee-collection?date_from=2026-07-01&date_to=2026-07-01')
            ->assertOk();

        // Sanctum caches the resolved user within a test — without forgetting the
        // guard here, the next request silently re-authenticates as the accountant
        // instead of the teacher token just supplied (see SESSION_START.md's test
        // gotchas, first hit in the Leave module).
        $this->app['auth']->forgetGuards();

        $this->withToken($this->teacherToken())
            ->getJson('/api/v2/reports/fee-collection?date_from=2026-07-01&date_to=2026-07-01')
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v2/reports/fee-collection?date_from=2026-07-01&date_to=2026-07-01')
            ->assertUnauthorized();
    }

    public function test_date_range_is_required(): void
    {
        $this->withToken($this->adminToken())
            ->getJson('/api/v2/reports/fee-collection')
            ->assertUnprocessable();
    }
}
