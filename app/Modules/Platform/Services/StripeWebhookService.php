<?php

namespace App\Modules\Platform\Services;

use App\Modules\Platform\Gateways\PaymentGatewayContract;
use App\Modules\Platform\Models\PendingSchoolSignup;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
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

        // Whole completion runs in one transaction with the signup row locked, so
        // two near-simultaneous duplicate deliveries can't both clear the status
        // guard and both provision. The second blocks on lockForUpdate, then reads
        // status = 'completed' and no-ops. (The subdomain unique index was the only
        // prior safeguard — this makes idempotency explicit rather than incidental.)
        DB::transaction(function () use ($pendingSignupId, $session): void {
            $signup = PendingSchoolSignup::whereKey($pendingSignupId)->lockForUpdate()->first();

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
        });
    }
}
