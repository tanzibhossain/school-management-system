<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\FeeItem\Models\FeeCategory;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\Payment\Models\Invoice;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Finance area (fee setup, invoices, payments, refunds, config).
 */
class FinanceAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private AcademicYear $year;

    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT', 'country_code' => 'BD',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
    }

    private function enrolStudent(string $admission): Student
    {
        $student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Student ' . $admission,
            'gender' => 'male', 'admission_number' => $admission, 'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id, 'student_id' => $student->id,
            'academic_year_id' => $this->year->id, 'class_id' => $this->class->id,
            'section_id' => Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => $admission])->id,
            'is_current' => true,
        ]);

        return $student;
    }

    private function makeFeeItem(float $amount = 5000): FeeItem
    {
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'is_active' => true]);

        return FeeItem::create([
            'school_id' => $this->school->id, 'category_id' => $cat->id,
            'academic_year_id' => $this->year->id, 'class_id' => $this->class->id,
            'name' => 'Monthly tuition', 'amount' => $amount, 'frequency' => 'monthly',
            'is_mandatory' => true, 'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/invoices')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_finance_screens(): void
    {
        $this->actingAs($this->admin);
        foreach (['/admin/fee-categories', '/admin/fee-items', '/admin/fee-discounts', '/admin/invoices', '/admin/payments', '/admin/refunds', '/admin/payment-config'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    // ── Fee setup ───────────────────────────────────────────────────────────────

    public function test_fee_category_crud_and_delete_guard(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/fee-categories', ['name' => 'Tuition', 'is_active' => 1])->assertRedirect();
        $cat = FeeCategory::where('school_id', $this->school->id)->firstOrFail();

        FeeItem::create([
            'school_id' => $this->school->id, 'category_id' => $cat->id, 'academic_year_id' => $this->year->id,
            'name' => 'x', 'amount' => 1, 'frequency' => 'monthly', 'is_active' => true,
        ]);

        $this->delete("/admin/fee-categories/{$cat->id}")->assertRedirect();
        $this->assertDatabaseHas('fee_categories', ['id' => $cat->id]); // blocked
    }

    public function test_can_create_fee_item_and_discount(): void
    {
        $this->actingAs($this->admin);
        $cat = FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'is_active' => true]);

        $this->post('/admin/fee-items', [
            'category_id' => $cat->id, 'academic_year_id' => $this->year->id, 'class_id' => $this->class->id,
            'name' => 'Monthly tuition', 'amount' => 5000, 'frequency' => 'monthly', 'is_mandatory' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('fee_items', ['school_id' => $this->school->id, 'name' => 'Monthly tuition', 'amount' => 5000]);

        $this->post('/admin/fee-discounts', ['name' => 'Sibling', 'type' => 'percentage', 'value' => 10])->assertRedirect();
        $this->assertDatabaseHas('fee_discounts', ['school_id' => $this->school->id, 'name' => 'Sibling', 'type' => 'percentage']);
    }

    public function test_percentage_discount_over_100_is_rejected(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/fee-discounts', ['name' => 'Bad', 'type' => 'percentage', 'value' => 150])
            ->assertSessionHasErrors('value');
    }

    // ── Invoices + payments ─────────────────────────────────────────────────────

    public function test_generate_single_invoice_and_record_payment(): void
    {
        $this->actingAs($this->admin);
        $this->makeFeeItem(5000);
        $student = $this->enrolStudent('ADM-1');

        $this->post('/admin/invoices/generate-single', [
            'student_id' => $student->id, 'academic_year_id' => $this->year->id, 'month' => 1, 'due_date' => '2026-01-31',
        ])->assertRedirect();

        $invoice = Invoice::where('school_id', $this->school->id)->where('student_id', $student->id)->firstOrFail();
        $this->assertEquals('5000.00', $invoice->amount_due);

        $this->post("/admin/invoices/{$invoice->id}/payments", ['amount' => 5000, 'method' => 'cash'])->assertRedirect();

        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id, 'amount' => 5000, 'method' => 'cash']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }

    public function test_generate_without_fee_items_flashes_error(): void
    {
        $this->actingAs($this->admin);
        $student = $this->enrolStudent('ADM-2');

        $this->post('/admin/invoices/generate-single', [
            'student_id' => $student->id, 'academic_year_id' => $this->year->id, 'month' => 1, 'due_date' => '2026-01-31',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseMissing('invoices', ['student_id' => $student->id]);
    }

    public function test_bulk_generate_by_class(): void
    {
        $this->actingAs($this->admin);
        $this->makeFeeItem(3000);
        $this->enrolStudent('ADM-3');
        $this->enrolStudent('ADM-4');

        $this->post('/admin/invoices/generate-bulk', [
            'class_id' => $this->class->id, 'academic_year_id' => $this->year->id, 'month' => 2, 'due_date' => '2026-02-28',
        ])->assertRedirect();

        $this->assertEquals(2, Invoice::where('school_id', $this->school->id)->where('month', 2)->count());
    }

    public function test_can_update_payment_config(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/payment-config', [
            'payment_mode' => 'both',
            'invoice_prefix' => 'INV-', 'receipt_prefix' => 'RCP-', 'bounce_fee_amount' => 50,
            'gw' => ['bkash' => [
                'enabled' => '1',
                'cred' => ['app_key' => 'test-app-key', 'app_secret' => 'secret', 'username' => 'user', 'password' => 'pass'],
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('payment_configs', [
            'school_id' => $this->school->id, 'invoice_prefix' => 'INV-', 'receipt_prefix' => 'RCP-', 'payment_mode' => 'both',
        ]);

        // Stored in the generic JSON store and readable back through the model.
        $config = \App\Modules\Payment\Models\PaymentConfig::where('school_id', $this->school->id)->first();
        $this->assertTrue($config->gatewayEnabled('bkash'));
        $this->assertSame('test-app-key', $config->credential('bkash', 'app_key'));
    }

    public function test_blank_credential_does_not_wipe_stored_key(): void
    {
        $this->actingAs($this->admin);

        // Store a credential (gateway left disabled, so no required-field check).
        $this->put('/admin/payment-config', ['payment_mode' => 'online', 'gw' => ['bkash' => ['cred' => ['app_key' => 'keep-me']]]])->assertRedirect();
        // Second save without the key must not clear it.
        $this->put('/admin/payment-config', ['payment_mode' => 'online'])->assertRedirect();

        $config = \App\Modules\Payment\Models\PaymentConfig::where('school_id', $this->school->id)->first();
        $this->assertSame('keep-me', $config->credential('bkash', 'app_key'));
    }
}
