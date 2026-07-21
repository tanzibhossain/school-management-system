<?php

namespace Tests\Feature\Report;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Payment\Models\Invoice;

class OutstandingDuesReportTest extends ReportTestCase
{
    private function makeInvoice(int $studentId, float $due, float $paid = 0, string $status = 'unpaid'): Invoice
    {
        return Invoice::create([
            'school_id' => $this->school->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'student_id' => $studentId,
            'academic_year_id' => $this->year->id,
            'amount_due' => $due,
            'currency' => 'USD',
            'amount_paid' => $paid,
            'status' => $status,
            'due_date' => '2026-07-01',
            'issued_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_view_outstanding_dues(): void
    {
        $student = $this->makeStudent();
        $this->makeInvoice($student->id, 300, 0, 'unpaid');

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/reports/outstanding-dues')
            ->assertOk()
            ->assertJsonFragment(['student_count' => 1, 'invoice_count' => 1])
            ->assertJsonFragment(['total_due' => 300.0])
            ->assertJsonCount(1, 'data.students');
    }

    public function test_partial_invoices_show_the_remaining_amount_only(): void
    {
        $student = $this->makeStudent();
        $this->makeInvoice($student->id, 500, 200, 'partial');

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/reports/outstanding-dues')
            ->assertOk()
            ->assertJsonFragment(['total_due' => 300.0]);
    }

    public function test_paid_invoices_are_excluded(): void
    {
        $student = $this->makeStudent();
        $this->makeInvoice($student->id, 300, 300, 'paid');

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/reports/outstanding-dues')
            ->assertOk()
            ->assertJsonFragment(['student_count' => 0]);
    }

    public function test_multiple_unpaid_invoices_for_one_student_are_combined(): void
    {
        $student = $this->makeStudent();
        $this->makeInvoice($student->id, 200, 0, 'unpaid');
        $this->makeInvoice($student->id, 150, 50, 'partial');

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/reports/outstanding-dues')
            ->assertOk()
            ->assertJsonFragment(['student_count' => 1, 'invoice_count' => 2])
            ->assertJsonFragment(['total_due' => 300.0]);
    }

    public function test_class_filter_scopes_results(): void
    {
        $otherClass = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 9']);
        $otherSection = Section::create(['school_id' => $this->school->id, 'class_id' => $otherClass->id, 'name' => 'A']);

        $inClass = $this->makeStudent();
        $outOfClass = $this->makeStudent($otherClass->id, $otherSection->id);

        $this->makeInvoice($inClass->id, 100);
        $this->makeInvoice($outOfClass->id, 200);

        $this->withToken($this->adminToken())
            ->getJson("/api/v2/reports/outstanding-dues?class_id={$this->class->id}")
            ->assertOk()
            ->assertJsonFragment(['student_count' => 1])
            ->assertJsonFragment(['total_due' => 100.0]);
    }

    public function test_pdf_export_renders_a_real_pdf(): void
    {
        $student = $this->makeStudent();
        $this->makeInvoice($student->id, 300);

        $response = $this->withToken($this->adminToken())
            ->get('/api/v2/reports/outstanding-dues?format=pdf')
            ->assertOk();

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_teacher_cannot_view(): void
    {
        $this->withToken($this->teacherToken())
            ->getJson('/api/v2/reports/outstanding-dues')
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v2/reports/outstanding-dues')->assertUnauthorized();
    }
}
