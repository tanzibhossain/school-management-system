<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\School\Models\School;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Family-portal online payment — the guards that run before any gateway call
 * (availability + invoice ownership). The gateway round-trip itself is exercised
 * by the Payment module's own tests.
 */
class PortalPaymentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT', 'country_code' => 'BD',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
    }

    private function familyUser(): User
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole('student');

        return $user;
    }

    public function test_initiate_blocked_when_online_payment_is_off(): void
    {
        PaymentConfig::create(['school_id' => $this->school->id, 'payment_mode' => 'offline']);

        $this->actingAs($this->familyUser());
        $this->post('/portal/pay/initiate', ['invoice_id' => 1, 'gateway' => 'bkash'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_initiate_rejects_an_invoice_not_owned_by_the_family(): void
    {
        // Online + bKash fully configured so we get past the availability check.
        PaymentConfig::create([
            'school_id' => $this->school->id, 'payment_mode' => 'online',
            'gateways' => ['bkash' => ['enabled' => true, 'credentials' => [
                'app_key' => 'k', 'app_secret' => 's', 'username' => 'u', 'password' => 'p',
            ]]],
        ]);

        $this->actingAs($this->familyUser());
        // No invoice with this id belongs to the (student-less) user → rejected
        // before any gateway call.
        $this->post('/portal/pay/initiate', ['invoice_id' => 999, 'gateway' => 'bkash'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_sslcommerz_initiate_blocked_when_gateway_not_enabled(): void
    {
        // Online + only bKash configured — SSLCommerz is not an enabled gateway.
        PaymentConfig::create([
            'school_id' => $this->school->id, 'payment_mode' => 'online',
            'gateways' => ['bkash' => ['enabled' => true, 'credentials' => [
                'app_key' => 'k', 'app_secret' => 's', 'username' => 'u', 'password' => 'p',
            ]]],
        ]);

        $this->actingAs($this->familyUser());
        $this->post('/portal/pay/initiate', ['invoice_id' => 1, 'gateway' => 'sslcommerz'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_sslcommerz_return_fail_redirects_to_fees_with_error(): void
    {
        // Public browser-return route — no auth, no gateway call for a failure.
        $this->post('/portal/pay/sslcommerz/fail', ['status' => 'FAILED'])
            ->assertRedirect(route('portal.fees'))
            ->assertSessionHas('error');
    }

    public function test_stripe_not_offered_for_a_bd_school(): void
    {
        // Stripe is a default (non-BD) gateway, so a BD school can never enable it.
        PaymentConfig::create([
            'school_id' => $this->school->id, 'payment_mode' => 'online',
            'gateways' => ['stripe' => ['enabled' => true, 'credentials' => ['secret_key' => 'sk_test']]],
        ]);

        $this->actingAs($this->familyUser());
        $this->post('/portal/pay/initiate', ['invoice_id' => 1, 'gateway' => 'stripe'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_stripe_return_without_session_is_treated_as_cancel(): void
    {
        // Public GET return with no session_id — cancelled, no gateway call.
        $this->get('/portal/pay/stripe/return')
            ->assertRedirect(route('portal.fees'))
            ->assertSessionHas('error');
    }

    public function test_paypal_not_offered_for_a_bd_school(): void
    {
        // PayPal is a default (non-BD) gateway, so a BD school can never enable it.
        PaymentConfig::create([
            'school_id' => $this->school->id, 'payment_mode' => 'online',
            'gateways' => ['paypal' => ['enabled' => true, 'credentials' => [
                'client_id' => 'cid', 'client_secret' => 'csec',
            ]]],
        ]);

        $this->actingAs($this->familyUser());
        $this->post('/portal/pay/initiate', ['invoice_id' => 1, 'gateway' => 'paypal'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_paypal_return_cancel_redirects_with_error(): void
    {
        // Public GET return with ?cancel=1 — cancelled, no gateway call.
        $this->get('/portal/pay/paypal/return?cancel=1')
            ->assertRedirect(route('portal.fees'))
            ->assertSessionHas('error');
    }
}
