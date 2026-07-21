<?php

namespace Tests\Feature\Payment;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\FeeItem\Models\FeeCategory;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $year;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'currency' => 'BDT', 'is_active' => true]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'year' => '2026',
            'is_current' => true,
        ]);

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
            'class_id' => null,
            'name' => 'Tuition Fee',
            'amount' => $amount,
            'frequency' => 'monthly',
            'is_mandatory' => true,
            'is_active' => true,
        ]);
    }

    // ── Tests ────────────────────────────────────────────────────────────────

    public function test_generated_invoice_inherits_school_currency(): void
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
            ->assertJsonFragment(['currency' => 'BDT']);

        $this->assertDatabaseHas('invoices', [
            'student_id' => $this->student->id,
            'currency' => 'BDT',
        ]);
    }

    public function test_manual_payment_copies_invoice_currency(): void
    {
        $this->makeFeeItem();
        $token = $this->token();

        $this->withToken($token)->postJson('/api/v2/invoices/generate', [
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'month' => 1,
            'due_date' => '2026-01-31',
        ])->assertCreated();

        $invoice = Invoice::firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v2/payments/invoices/{$invoice->id}/record", [
                'amount' => 1000,
                'method' => 'cash',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'currency' => 'BDT',
        ]);
    }

    public function test_bkash_rejects_non_bdt_invoice(): void
    {
        $this->school->update(['currency' => 'USD']);
        $this->makeFeeItem();

        $this->withToken($this->token())->postJson('/api/v2/invoices/generate', [
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'month' => 1,
            'due_date' => '2026-01-31',
        ])->assertCreated();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('USD', $invoice->currency);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bKash does not support USD');

        app(PaymentService::class)->initiateBkash($invoice, 'https://example.test/callback');
    }
}
