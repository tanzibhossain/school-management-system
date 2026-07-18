<?php

namespace Tests\Feature\Payment;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\Payment\Services\RefundService;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Automatic gateway refunds for Stripe and PayPal — the drivers are invoked
 * through PaymentGatewayManager and the refund status reflects the gateway result.
 */
class RefundGatewayTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin  = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $student = Student::create([
            'school_id' => $this->school->id, 'admission_number' => 'ADM-1',
            'name' => 'Test Student', 'gender' => 'male', 'status' => 'active',
        ]);
        $this->invoice = Invoice::create([
            'school_id' => $this->school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id,
            'invoice_number' => 'INV-1', 'credit_applied' => 0, 'amount_due' => 5000, 'amount_paid' => 5000,
            'status' => 'paid', 'due_date' => '2026-01-31', 'issued_by' => $this->admin->id,
        ]);
    }

    private function payment(string $method, string $gatewayPaymentId): Payment
    {
        return Payment::create([
            'school_id' => $this->school->id, 'receipt_number' => 'RCP-' . $method,
            'invoice_id' => $this->invoice->id, 'student_id' => $this->invoice->student_id,
            'amount' => 5000, 'currency' => 'USD', 'method' => $method,
            'transaction_ref' => $gatewayPaymentId, 'gateway_payment_id' => $gatewayPaymentId,
            'gateway_status' => 'success', 'collected_by' => $this->admin->id, 'paid_at' => now(),
        ]);
    }

    private function config(string $slug, array $credentials): void
    {
        PaymentConfig::create([
            'school_id' => $this->school->id, 'payment_mode' => 'online',
            'gateways' => [$slug => ['enabled' => true, 'credentials' => $credentials]],
        ]);
    }

    public function test_stripe_refund_calls_the_gateway_and_completes(): void
    {
        Http::fake(['https://api.stripe.com/*' => Http::response(['id' => 're_123', 'status' => 'succeeded'])]);
        $this->config('stripe', ['secret_key' => 'sk_test']);
        $payment = $this->payment('stripe', 'pi_123');

        $refund = app(RefundService::class)->request($payment, 5000, $this->admin->id);

        $this->assertSame('completed', $refund->status);
        $this->assertSame('re_123', $refund->gateway_ref);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.stripe.com/v1/refunds'));
    }

    public function test_paypal_refund_calls_the_gateway_and_completes(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token'        => Http::response(['access_token' => 'tok']),
            'https://api-m.sandbox.paypal.com/v2/payments/captures/*' => Http::response(['id' => 'ref_1', 'status' => 'COMPLETED']),
        ]);
        $this->config('paypal', ['client_id' => 'cid', 'client_secret' => 'csec', 'mode' => 'sandbox']);
        $payment = $this->payment('paypal', 'CAPTURE1');

        $refund = app(RefundService::class)->request($payment, 5000, $this->admin->id);

        $this->assertSame('completed', $refund->status);
        $this->assertSame('ref_1', $refund->gateway_ref);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v2/payments/captures/CAPTURE1/refund'));
    }

    public function test_gateway_refund_requires_a_successful_payment(): void
    {
        $this->config('stripe', ['secret_key' => 'sk_test']);
        $payment = $this->payment('stripe', 'pi_123');
        $payment->update(['gateway_status' => 'pending']);

        $this->expectException(\RuntimeException::class);
        app(RefundService::class)->request($payment, 5000, $this->admin->id);
    }
}
