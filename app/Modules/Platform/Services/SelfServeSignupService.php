<?php

namespace App\Modules\Platform\Services;

use App\Modules\Platform\Gateways\PaymentGatewayContract;
use App\Modules\Platform\Models\PendingSchoolSignup;
use App\Modules\Platform\Models\Plan;
use App\Modules\School\Models\School;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Public, unauthenticated signup entry points. Trial provisions IMMEDIATELY (free,
 * nothing to wait for). Paid plans (Basic/Pro) go through Stripe Checkout first —
 * see StripeWebhookService for what happens when payment is confirmed.
 */
class SelfServeSignupService
{
    public function __construct(
        private readonly PlanService $plans,
        private readonly SchoolProvisioningService $provisioning,
        private readonly PaymentGatewayContract $gateway,
    ) {}

    /**
     * @param array{school_name: string, subdomain: string, admin_name: string, admin_email: string, country_code?: string|null} $data
     */
    public function trial(array $data): School
    {
        $plan = $this->plans->findBySlugOrFail('trial');
        $this->assertSubdomainAvailable($data['subdomain']);

        return $this->provisioning->provision(
            $data,
            $plan,
            'self_service',
            trialEndsAt: now()->addDays($plan->trial_days ?? 30),
        );
    }

    /**
     * @param array{school_name: string, subdomain: string, admin_name: string, admin_email: string, country_code?: string|null, plan_id: int, billing_cycle: string} $data
     * @return array{checkout_url: string}
     */
    public function startPaidCheckout(array $data): array
    {
        $plan = $this->plans->findOrFail($data['plan_id']);

        if (! $plan->is_self_serve || $plan->slug === 'trial') {
            throw new UnprocessableEntityHttpException("Plan '{$plan->slug}' is not available for direct checkout.");
        }

        $this->assertSubdomainAvailable($data['subdomain']);

        $signup = PendingSchoolSignup::create([
            'school_name' => $data['school_name'],
            'desired_subdomain' => $data['subdomain'],
            'plan_id' => $plan->id,
            'admin_name' => $data['admin_name'],
            'admin_email' => $data['admin_email'],
            'country_code' => $data['country_code'] ?? null,
            'status' => 'pending',
        ]);

        $interval = $data['billing_cycle'] === 'yearly' ? 'year' : 'month';
        $price = $interval === 'year' ? $plan->price_yearly : $plan->price_monthly;

        $session = $this->gateway->createCheckoutSession([
            'admin_email' => $data['admin_email'],
            'plan_name' => $plan->name,
            'currency' => $plan->currency,
            'amount_cents' => (int) round(((float) $price) * 100),
            'interval' => $interval,
            'success_url' => Config::get('app.frontend_url', 'https://example.com') . '/signup/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => Config::get('app.frontend_url', 'https://example.com') . '/signup/cancelled',
            'metadata' => ['pending_signup_id' => (string) $signup->id],
        ]);

        $signup->update(['stripe_checkout_session_id' => $session['session_id']]);

        return ['checkout_url' => $session['checkout_url']];
    }

    private function assertSubdomainAvailable(string $subdomain): void
    {
        if (School::where('subdomain', $subdomain)->exists()) {
            throw new UnprocessableEntityHttpException("Subdomain '{$subdomain}' is already taken.");
        }
    }
}
