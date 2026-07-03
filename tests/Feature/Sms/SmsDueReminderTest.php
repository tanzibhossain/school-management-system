<?php

namespace Tests\Feature\Sms;

use App\Modules\Payment\Models\Invoice;

class SmsDueReminderTest extends SmsTestCase
{
    private function makeInvoice(int $studentId, float $due, float $paid = 0, string $status = 'unpaid'): Invoice
    {
        return Invoice::create([
            'school_id' => $this->school->id,
            'invoice_number' => 'INV-'.$studentId.'-'.$status,
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

    public function test_admin_can_send_due_reminders_for_students_with_dues(): void
    {
        $student = $this->makeStudent();
        $this->makeInvoice($student->id, 500);

        $response = $this->withToken($this->adminToken())
            ->postJson('/api/v2/sms/due-reminders', ['scope' => 'all'])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'completed', 'total_count' => 1])
            ->assertJsonCount(1, 'data.logs');

        $log = $response->json('data.logs.0');
        $this->assertSame('sent', $log['status']);
        $this->assertSame('due_reminder', $log['purpose']);
        $this->assertStringContainsString($student->name, $log['body']);
        $this->assertStringContainsString('500.00', $log['body']);
        $this->assertStringContainsString('USD', $log['body']);
    }

    public function test_students_with_only_paid_invoices_are_excluded(): void
    {
        $student = $this->makeStudent();
        $this->makeInvoice($student->id, 300, 300, 'paid');

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/sms/due-reminders', ['scope' => 'all'])
            ->assertCreated()
            ->assertJsonFragment(['total_count' => 0]);
    }

    public function test_class_filter_scopes_reminders(): void
    {
        $otherClass = \App\Modules\Academic\Models\SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 9']);
        $otherSection = \App\Modules\Academic\Models\Section::create(['school_id' => $this->school->id, 'class_id' => $otherClass->id, 'name' => 'A']);

        $inClass = $this->makeStudent();
        $outOfClass = $this->makeStudent($otherClass->id, $otherSection->id);

        $this->makeInvoice($inClass->id, 100);
        $this->makeInvoice($outOfClass->id, 200);

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/sms/due-reminders', ['scope' => 'class', 'class_id' => $this->class->id])
            ->assertCreated()
            ->assertJsonFragment(['total_count' => 1]);
    }

    public function test_accountant_can_send_but_teacher_cannot(): void
    {
        $student = $this->makeStudent();
        $this->makeInvoice($student->id, 100);

        $this->withToken($this->accountantToken())
            ->postJson('/api/v2/sms/due-reminders', ['scope' => 'all'])
            ->assertCreated();

        $this->app['auth']->forgetGuards();

        $this->withToken($this->teacherToken())
            ->postJson('/api/v2/sms/due-reminders', ['scope' => 'all'])
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v2/sms/due-reminders', ['scope' => 'all'])->assertUnauthorized();
    }
}
