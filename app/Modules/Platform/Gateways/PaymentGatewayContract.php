<?php

namespace App\Modules\Platform\Gateways;

/**
 * Vendor-side billing gateway (bills a SCHOOL for using the platform). Entirely
 * separate from Payment module's per-school gateways (bKash/SSLCommerz/Stripe/
 * PayPal), which bill a school's OWN students. Only one implementation exists
 * today (Stripe, globally) — this contract exists so a second processor could be
 * added later without touching CheckoutController/StripeWebhookController.
 */
interface PaymentGatewayContract
{
    /**
     * Start a hosted checkout session for a subscription purchase.
     *
     * @param array{
     *     admin_email: string,
     *     plan_name: string,
     *     currency: string,
     *     amount_cents: int,
     *     interval: string,
     *     success_url: string,
     *     cancel_url: string,
     *     metadata: array<string, string>,
     * } $params
     * @return array{session_id: string, checkout_url: string}
     */
    public function createCheckoutSession(array $params): array;

    /**
     * Verify an inbound webhook actually came from the gateway before trusting
     * its payload (HMAC signature check — never process an unverified webhook).
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader, string $webhookSecret): bool;
}
