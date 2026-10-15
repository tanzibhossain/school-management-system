<?php

namespace Tests\Feature\Admin;

use App\Modules\Payment\Models\Invoice;
use App\Modules\Student\Models\Student;
use Carbon\Carbon;
use Tests\Feature\Report\ReportTestCase;

class DashboardTest extends ReportTestCase
{
    private function invoice(Student $student, Carbon $dueDate, string $status = 'unpaid'): void
    {
        Invoice::create([
            'school_id' => $this->school->id,
            'invoice_number' => $this->nextInvoiceNumber(),
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'amount_due' => 100,
            'currency' => 'USD',
            'amount_paid' => $status === 'paid' ? 100 : 0,
            'status' => $status,
            'due_date' => $dueDate->toDateString(),
            'issued_by' => $this->admin->id,
        ]);
    }

    public function test_fee_defaulters_are_ranked_by_all_their_overdue_invoices(): void
    {
        $longOverdue = $this->makeStudent();   // 5 overdue invoices, all old
        $mostOverdue = $this->makeStudent();   // 6 overdue invoices, more recent
        $paidUp = $this->makeStudent();

        for ($i = 0; $i < 5; $i++) {
            $this->invoice($longOverdue, Carbon::today()->subDays(100 + $i));
        }
        for ($i = 0; $i < 6; $i++) {
            $this->invoice($mostOverdue, Carbon::today()->subDays(10 + $i));
        }
        $this->invoice($longOverdue, Carbon::today()->addDays(10));             // not due yet
        $this->invoice($paidUp, Carbon::today()->subDays(200), 'paid');        // settled
        $this->invoice($paidUp, Carbon::today()->subDays(200), 'cancelled');   // cancelled

        $this->actingAs($this->admin)->get('/admin')
            ->assertOk()
            ->assertViewHas('feeDefaulters', function ($defaulters) use ($longOverdue, $mostOverdue) {
                $rows = collect($defaulters)->map(fn ($r) => [$r['id'], $r['overdue_count']])->all();

                // The old logic counted only the 5 oldest invoices, so it showed
                // [$longOverdue, 5] alone and left $mostOverdue off the list.
                return $rows === [[$mostOverdue->id, 6], [$longOverdue->id, 5]];
            });
    }
}
