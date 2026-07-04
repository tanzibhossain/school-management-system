<?php

namespace App\Modules\Platform\Gateways;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Calls Stripe's REST API directly via the Http facade — same pattern already used
 * for BkashGateway/SslcommerzGateway (Payment module) and AnthropicAiChecker (LMS
 * module), rather than adding the stripe-php SDK as a new Composer dependency.
 * Tests fake this via Http::fake(['api.stripe.com/*' => ...]), never hitting the
 * real API.
 */
class StripeGateway implements PaymentGatewayContract
{
    public function createCheckoutSession(array $params): array
    {
        $secretKey = config('platform.stripe.secret_key');

        $response = Http::withToken($secretKey)
            ->asForm()
            ->post(config('platform.stripe.api_base') . '/checkout/sessions', [
                'mode' => 'subscription',
                'payment_method_types' => ['card'],
                'customer_email' => $params['admin_email'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($params['currency']),
                        'product_data' => [
                            'name' => $params['plan_name'],
                        ],
                        'unit_amount' => $params['amount_cents'],
                        'recurring' => [
                            'interval' => $params['interval'],
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $params['success_url'],
                'cancel_url' => $params['cancel_url'],
                'metadata' => $params['metadata'] ?? [],
            ]);

        if (! $response->successful() || empty($response->json('id'))) {
            throw new RuntimeException('Stripe checkout session creation failed: ' . $response->body());
        }

        return [
            'session_id' => $response->json('id'),
            'checkout_url' => $response->json('url'),
        ];
    }

    /**
     * Stripe's webhook signature scheme: the `Stripe-Signature` header looks like
     * "t=1614556800,v1=5257a869e7...". Expected signature = HMAC-SHA256 of
     * "{timestamp}.{raw_payload}" using the webhook signing secret, hex-encoded.
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader, string $webhookSecret): bool
    {
        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            if ($key !== null) {
                $parts[$key] = $value;
            }
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $expected = hash_hmac('sha256', $parts['t'] . '.' . $payload, $webhookSecret);

        return hash_equals($expected, $parts['v1']);
    }
}
