<?php

namespace App\Modules\Payment\Gateways;

use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\Payment\Models\PaymentGatewayLog;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Stripe Checkout (hosted) driver.
 *
 * A hosted Checkout Session keeps card data off our servers (like SSLCommerz's
 * hosted page): create a session, redirect the browser to Stripe, and confirm
 * the result on return. Talks to the Stripe REST API directly (form-encoded,
 * Bearer secret key) — no SDK dependency, matching the other gateways.
 */
class StripeGateway
{
    /** Currencies we expose for Stripe (mirrors the registry). */
    public const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD', 'INR', 'AED', 'JPY', 'NZD'];

    /** Stripe zero-decimal currencies — amounts are whole units, not cents. */
    private const ZERO_DECIMAL = [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA',
        'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    private const API_BASE = 'https://api.stripe.com/v1';

    public function __construct(private readonly PaymentConfig $config) {}

    /**
     * Create a hosted Checkout Session for an invoice.
     *
     * @return array{ id: string, url: string }
     */
    public function createCheckoutSession(string $invoiceNumber, float $amount, string $currency, string $successUrl, string $cancelUrl, int $invoiceId, int $schoolId): array
    {
        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $invoiceNumber,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($currency),
                    'unit_amount' => $this->toMinorUnits($amount, $currency),
                    'product_data' => ['name' => "Invoice {$invoiceNumber}"],
                ],
            ]],
            'metadata' => ['invoice_id' => $invoiceId, 'school_id' => $schoolId],
        ];

        $response = Http::withToken($this->secretKey())->asForm()
            ->post(self::API_BASE.'/checkout/sessions', $payload);

        $this->log(null, 'create_session', $payload, $response->json(), $response->status());

        if (! $response->successful() || empty($response->json('url'))) {
            throw new RuntimeException('Stripe checkout session failed: '.$response->body());
        }

        return ['id' => $response->json('id'), 'url' => $response->json('url')];
    }

    /**
     * Retrieve a Checkout Session to confirm payment status on return.
     *
     * @return array<string, mixed>
     */
    public function retrieveSession(string $sessionId): array
    {
        $response = Http::withToken($this->secretKey())
            ->get(self::API_BASE.'/checkout/sessions/'.$sessionId);

        $this->log(null, 'retrieve_session', ['id' => $sessionId], $response->json(), $response->status());

        if (! $response->successful()) {
            throw new RuntimeException('Stripe session retrieval failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Refund a captured PaymentIntent (full or partial).
     *
     * @return array<string, mixed>
     */
    public function refund(string $paymentIntentId, float $amount, string $currency): array
    {
        $payload = [
            'payment_intent' => $paymentIntentId,
            'amount' => $this->toMinorUnits($amount, $currency),
        ];

        $response = Http::withToken($this->secretKey())->asForm()
            ->post(self::API_BASE.'/refunds', $payload);

        $this->log(null, 'refund', $payload, $response->json(), $response->status());

        if (! $response->successful()) {
            throw new RuntimeException('Stripe refund failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Verify a Stripe webhook signature (HMAC-SHA256 over "{t}.{payload}") against
     * the stored signing secret. No SDK — implements the documented scheme.
     */
    public function verifyWebhookSignature(string $payload, ?string $sigHeader, int $toleranceSeconds = 300): bool
    {
        $secret = $this->config->credential('stripe', 'webhook_secret');
        if (! filled($secret) || ! filled($sigHeader)) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $sigHeader) as $piece) {
            [$k, $v] = array_pad(explode('=', trim($piece), 2), 2, null);
            $parts[$k][] = $v;
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];
        if (! $timestamp || ! $signatures) {
            return false;
        }

        // Reject stale timestamps (replay protection).
        if (abs(time() - (int) $timestamp) > $toleranceSeconds) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $candidate) {
            if (is_string($candidate) && hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /** Convert a minor-unit amount (from Stripe) back to a major-unit decimal. */
    public function toMajorUnits(int $minor, string $currency): float
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true)
            ? (float) $minor
            : round($minor / 100, 2);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function toMinorUnits(float $amount, string $currency): int
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true)
            ? (int) round($amount)
            : (int) round($amount * 100);
    }

    private function secretKey(): string
    {
        $key = $this->config->credential('stripe', 'secret_key');

        if (! filled($key)) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        return $key;
    }

    private function log(?int $paymentId, string $action, array $payload, ?array $response, int $httpStatus): void
    {
        PaymentGatewayLog::create([
            'school_id' => $this->config->school_id,
            'payment_id' => $paymentId,
            'gateway' => 'stripe',
            'action' => $action,
            'payload' => $payload,
            'response' => $response ?? [],
            'status' => (string) $httpStatus,
        ]);
    }
}
