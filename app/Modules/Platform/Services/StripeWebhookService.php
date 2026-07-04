<?php

namespace App\Modules\Platform\Services;

use App\Modules\Platform\Gateways\PaymentGatewayContract;
use App\Modules\Platform\Models\PendingSchoolSignup;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Completes the paid self-serve signup path once Stripe confirms payment. The
 * PendingSchoolSignup row (created by SelfServeSignupService::startPaidCheckout)
 * survives the round-trip out to Stripe Checkout and back here.
 */
class StripeWebhookService
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly SchoolProvisioningService $provisioning,
        private readonly PlanService $plans,
    ) {}

    public function handle(string $rawPayload, ?string $signatureHeader): void
    {
        $webhookSecret = Config::get('platform.stripe.webhook_secret');

        if (! $signatureHeader || ! $webhookSecret
            || ! $this->gateway->verifyWebhookSignature($rawPayload, $signatureHeader, $webhookSecret)
        ) {
            throw new AccessDeniedHttpException('Invalid Stripe webhook signature.');
        }

        $event = json_decode($rawPayload, true);

        if (($event['type'] ?? null) !== 'checkout.session.completed') {
            return; // Not an event we act on — acknowledge and ignore.
        }

        $session = $event['data']['object'] ?? [];
        $sessionId = $session['id'] ?? null;
        $pendingSignupId = $session['metadata']['pending_signup_id'] ?? null;

        if (! $sessionId || ! $pendingSignupId) {
            return;
        }

        $signup = PendingSchoolSignup::find($pendingSignupId);

        // Idempotent: Stripe retries webhook delivery — a second delivery for an
        // already-completed signup is a silent no-op, never a duplicate school.
        if (! $signup || $signup->status === 'completed') {
            return;
        }

        $plan = $this->plans->findOrFail($signup->plan_id);

        $school = $this->provisioning->provision(
            [
                'school_name' => $signup->school_name,
                'subdomain' => $signup->desired_subdomain,
                'admin_name' => $signup->admin_name,
                'admin_email' => $signup->admin_email,
                'country_code' => $signup->country_code,
            ],
            $plan,
            'self_service',
            subscriptionExpiresAt: now()->addMonth(),
        );

        $school->update([
            'stripe_customer_id' => $session['customer'] ?? null,
            'stripe_subscription_id' => $session['subscription'] ?? null,
        ]);

        $signup->update([
            'status' => 'completed',
            'created_school_id' => $school->id,
        ]);
    }
}
