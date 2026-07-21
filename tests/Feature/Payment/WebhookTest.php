<?php

namespace Tests\Feature\Payment;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Gateway webhooks — the authoritative confirmation path. Signature is the trust
 * boundary; recording reuses the idempotent PaymentService::verify*.
 */
class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'is_active' => true, 'currency' => 'USD', 'country_code' => 'US']);
        $admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $student = Student::create([
            'school_id' => $this->school->id, 'admission_number' => 'ADM-1',
            'name' => 'Test Student', 'gender' => 'male', 'status' => 'active',
        ]);
        $this->invoice = Invoice::create([
            'school_id' => $this->school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id,
            'invoice_number' => 'INV-1', 'credit_applied' => 0, 'amount_due' => 5000, 'amount_paid' => 0,
            'status' => 'unpaid', 'due_date' => '2026-01-31', 'issued_by' => $admin->id,
        ]);
    }

    private function config(string $slug, array $credentials): void
    {
        PaymentConfig::create([
            'school_id' => $this->school->id, 'payment_mode' => 'online',
            'gateways' => [$slug => ['enabled' => true, 'credentials' => $credentials]],
        ]);
    }

    // ── Stripe ────────────────────────────────────────────────────────────────

    private function stripeEvent(): string
    {
        return json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_1',
                'metadata' => ['school_id' => $this->school->id, 'invoice_id' => $this->invoice->id],
            ]],
        ]);
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $t = time();

        return 't='.$t.',v1='.hash_hmac('sha256', $t.'.'.$payload, $secret);
    }

    public function test_stripe_webhook_records_payment_on_valid_signature(): void
    {
        Http::fake(['https://api.stripe.com/*' => Http::response([
            'id' => 'cs_1', 'payment_status' => 'paid', 'amount_total' => 500000,
            'currency' => 'usd', 'client_reference_id' => 'INV-1', 'payment_intent' => 'pi_1',
        ])]);
        $this->config('stripe', ['secret_key' => 'sk_test', 'webhook_secret' => 'whsec_test']);

        $payload = $this->stripeEvent();
        $sig = $this->stripeSignature($payload, 'whsec_test');

        $this->call('POST', '/payments/webhook/stripe', [], [], [],
            ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id, 'method' => 'stripe', 'transaction_ref' => 'pi_1',
        ]);
    }

    public function test_stripe_webhook_rejects_a_bad_signature(): void
    {
        $this->config('stripe', ['secret_key' => 'sk_test', 'webhook_secret' => 'whsec_test']);
        $payload = $this->stripeEvent();

        $this->call('POST', '/payments/webhook/stripe', [], [], [],
            ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=deadbeef', 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertStatus(400);

        $this->assertDatabaseMissing('payments', ['invoice_id' => $this->invoice->id]);
    }

    // ── PayPal ────────────────────────────────────────────────────────────────

    private function paypalHeaders(): array
    {
        return [
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA', 'PAYPAL-CERT-URL' => 'https://api.paypal.com/cert',
            'PAYPAL-TRANSMISSION-ID' => 'tid', 'PAYPAL-TRANSMISSION-SIG' => 'sig', 'PAYPAL-TRANSMISSION-TIME' => 'now',
        ];
    }

    private function fakePayPal(string $verifyStatus): void
    {
        Http::fake(function ($request) use ($verifyStatus) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/oauth2/token') => Http::response(['access_token' => 'tok']),
                str_contains($url, '/verify-webhook-signature') => Http::response(['verification_status' => $verifyStatus]),
                str_contains($url, '/capture') => Http::response([
                    'status' => 'COMPLETED',
                    'purchase_units' => [['custom_id' => 'INV-1', 'payments' => ['captures' => [[
                        'id' => 'CAP1', 'custom_id' => 'INV-1', 'amount' => ['value' => '5000.00', 'currency_code' => 'USD'],
                    ]]]]],
                ]),
                str_contains($url, '/v2/checkout/orders/ORDER1') => Http::response([
                    'status' => 'APPROVED', 'purchase_units' => [['custom_id' => 'INV-1']],
                ]),
                default => Http::response([], 404),
            };
        });
    }

    private function paypalEvent(): array
    {
        return [
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource' => ['id' => 'ORDER1', 'purchase_units' => [['custom_id' => 'INV-1']]],
        ];
    }

    public function test_paypal_webhook_captures_and_records_on_valid_signature(): void
    {
        $this->fakePayPal('SUCCESS');
        $this->config('paypal', ['client_id' => 'cid', 'client_secret' => 'csec', 'mode' => 'sandbox', 'webhook_id' => 'WH-1']);

        $this->withHeaders($this->paypalHeaders())
            ->postJson('/payments/webhook/paypal', $this->paypalEvent())
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id, 'method' => 'paypal', 'transaction_ref' => 'CAP1',
        ]);
    }

    public function test_paypal_webhook_rejects_a_bad_signature(): void
    {
        $this->fakePayPal('FAILURE');
        $this->config('paypal', ['client_id' => 'cid', 'client_secret' => 'csec', 'mode' => 'sandbox', 'webhook_id' => 'WH-1']);

        $this->withHeaders($this->paypalHeaders())
            ->postJson('/payments/webhook/paypal', $this->paypalEvent())
            ->assertStatus(400);

        $this->assertDatabaseMissing('payments', ['invoice_id' => $this->invoice->id]);
    }
}
