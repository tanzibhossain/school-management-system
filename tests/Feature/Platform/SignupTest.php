<?php

namespace Tests\Feature\Platform;

use App\Modules\Platform\Mail\SetPasswordMail;
use App\Modules\Platform\Models\PendingSchoolSignup;
use App\Modules\School\Models\School;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SignupTest extends PlatformTestCase
{
    public function test_trial_signup_provisions_a_school_and_admin_immediately(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v2/platform/signup/trial', [
            'school_name' => 'Greenwood High',
            'subdomain' => 'greenwood',
            'admin_name' => 'Jane Admin',
            'admin_email' => 'jane@greenwood.example',
        ])->assertCreated();

        $this->assertDatabaseHas('schools', [
            'name' => 'Greenwood High',
            'subdomain' => 'greenwood',
            'provisioning_type' => 'self_service',
        ]);

        $school = School::where('subdomain', 'greenwood')->firstOrFail();
        $this->assertEquals($this->trialPlan->id, $school->plan_id);
        $this->assertNotNull($school->trial_ends_at);

        $this->assertDatabaseHas('users', ['email' => 'jane@greenwood.example', 'school_id' => $school->id]);

        // SetPasswordMail implements ShouldQueue, so Mailer::send() auto-redirects to
        // queue() — MailFake tracks that as "queued", not "sent" (hence assertQueued,
        // not assertSent, matching the fake's own suggestion when this was assertSent).
        Mail::assertQueued(SetPasswordMail::class, fn ($mail) => $mail->hasTo('jane@greenwood.example'));
    }

    public function test_trial_signup_rejects_duplicate_subdomain(): void
    {
        $this->postJson('/api/v2/platform/signup/trial', [
            'school_name' => 'A', 'subdomain' => 'dup', 'admin_name' => 'A', 'admin_email' => 'a@example.com',
        ])->assertCreated();

        $this->postJson('/api/v2/platform/signup/trial', [
            'school_name' => 'B', 'subdomain' => 'dup', 'admin_name' => 'B', 'admin_email' => 'b@example.com',
        ])->assertUnprocessable();
    }

    public function test_paid_checkout_creates_pending_signup_and_returns_stripe_url(): void
    {
        Http::fake([
            'api.stripe.com/*' => Http::response([
                'id' => 'cs_test_123',
                'url' => 'https://checkout.stripe.com/pay/cs_test_123',
            ], 200),
        ]);

        $response = $this->postJson('/api/v2/platform/signup/checkout', [
            'school_name' => 'Basic School',
            'subdomain' => 'basicschool',
            'admin_name' => 'Bob Admin',
            'admin_email' => 'bob@basicschool.example',
            'plan_id' => $this->basicPlan->id,
            'billing_cycle' => 'monthly',
        ])->assertCreated();

        $response->assertJsonFragment(['checkout_url' => 'https://checkout.stripe.com/pay/cs_test_123']);

        $this->assertDatabaseHas('pending_school_signups', [
            'school_name' => 'Basic School',
            'stripe_checkout_session_id' => 'cs_test_123',
            'status' => 'pending',
        ]);

        // No school exists yet — provisioning only happens once the webhook confirms payment.
        $this->assertDatabaseMissing('schools', ['subdomain' => 'basicschool']);
    }

    public function test_stripe_webhook_completes_provisioning_and_is_idempotent(): void
    {
        Mail::fake();
        config(['platform.stripe.webhook_secret' => 'whsec_test_secret']);

        $signup = PendingSchoolSignup::create([
            'school_name' => 'Paid School',
            'desired_subdomain' => 'paidschool',
            'plan_id' => $this->basicPlan->id,
            'admin_name' => 'Paid Admin',
            'admin_email' => 'paid@example.com',
            'stripe_checkout_session_id' => 'cs_test_456',
            'status' => 'pending',
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_456',
                'customer' => 'cus_123',
                'subscription' => 'sub_123',
                'metadata' => ['pending_signup_id' => (string) $signup->id],
            ]],
        ]);

        $signature = $this->stripeSignature($payload, 'whsec_test_secret');

        // First delivery — provisions the school.
        $this->call('POST', '/api/v2/platform/webhooks/stripe', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        $this->assertDatabaseHas('schools', ['subdomain' => 'paidschool', 'provisioning_type' => 'self_service']);
        $school = School::where('subdomain', 'paidschool')->firstOrFail();
        $this->assertEquals('cus_123', $school->stripe_customer_id);

        $this->assertDatabaseHas('pending_school_signups', ['id' => $signup->id, 'status' => 'completed']);

        // Second delivery (Stripe retries) — must NOT create a duplicate school.
        $this->call('POST', '/api/v2/platform/webhooks/stripe', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertOk();

        $this->assertEquals(1, School::where('subdomain', 'paidschool')->count());
    }

    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        config(['platform.stripe.webhook_secret' => 'whsec_test_secret']);

        $this->call('POST', '/api/v2/platform/webhooks/stripe', [], [], [], [
            'HTTP_Stripe-Signature' => 't=1,v1=bogus',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['type' => 'checkout.session.completed']))->assertForbidden();
    }

    public function test_set_password_flow(): void
    {
        Mail::fake();

        $this->postJson('/api/v2/platform/signup/trial', [
            'school_name' => 'SetPw School', 'subdomain' => 'setpw', 'admin_name' => 'Set Pw',
            'admin_email' => 'setpw@example.com',
        ])->assertCreated();

        $capturedUrl = null;
        Mail::assertQueued(SetPasswordMail::class, function ($mail) use (&$capturedUrl) {
            $capturedUrl = $mail->signedUrl;

            return true;
        });

        $this->assertNotNull($capturedUrl);
        $path = parse_url($capturedUrl, PHP_URL_PATH) . '?' . parse_url($capturedUrl, PHP_URL_QUERY);

        $this->postJson($path, [
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertOk();

        $admin = \App\Models\User::where('email', 'setpw@example.com')->firstOrFail();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brand-new-password', $admin->password));
    }

    public function test_set_password_rejects_invalid_signature(): void
    {
        $this->postJson('/api/v2/platform/set-password?user=1&expires=9999999999&signature=bogus', [
            'password' => 'whatever12345',
            'password_confirmation' => 'whatever12345',
        ])->assertForbidden();
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signed = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        return "t={$timestamp},v1={$signed}";
    }
}
