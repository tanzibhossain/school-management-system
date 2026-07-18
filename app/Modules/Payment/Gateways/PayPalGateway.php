<?php

namespace App\Modules\Payment\Gateways;

use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\Payment\Models\PaymentGatewayLog;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PayPal Orders v2 driver.
 *
 * Redirect flow: create an order, send the payer to the PayPal-hosted approve
 * link, then capture on return. Talks to the REST API directly (OAuth2 client
 * credentials, Bearer token) — no SDK, matching the other gateways.
 */
class PayPalGateway
{
    /** Currencies we expose for PayPal (mirrors the registry). */
    public const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD', 'JPY', 'NZD'];

    /** PayPal currencies that do not accept decimals. */
    private const ZERO_DECIMAL = ['JPY', 'HUF', 'TWD'];

    public function __construct(private readonly PaymentConfig $config) {}

    /**
     * Create an order and return its id + payer approval URL.
     *
     * @return array{ id: string, approveUrl: string }
     */
    public function createOrder(string $invoiceNumber, float $amount, string $currency, string $returnUrl, string $cancelUrl): array
    {
        $payload = [
            'intent'         => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $invoiceNumber,
                'custom_id'    => $invoiceNumber,
                'amount'       => [
                    'currency_code' => strtoupper($currency),
                    'value'         => $this->formatAmount($amount, $currency),
                ],
            ]],
            'application_context' => [
                'return_url'          => $returnUrl,
                'cancel_url'          => $cancelUrl,
                'user_action'         => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING',
            ],
        ];

        $response = Http::withToken($this->accessToken())->acceptJson()
            ->post($this->url('/v2/checkout/orders'), $payload);

        $this->log(null, 'create_order', $payload, $response->json(), $response->status());

        if (! $response->successful() || empty($response->json('id'))) {
            throw new RuntimeException('PayPal create order failed: ' . $response->body());
        }

        $approveLink = collect($response->json('links', []))->firstWhere('rel', 'approve');
        $approve = is_array($approveLink) ? ($approveLink['href'] ?? null) : null;
        if (! $approve) {
            throw new RuntimeException('PayPal approve link missing from order response.');
        }

        return ['id' => $response->json('id'), 'approveUrl' => $approve];
    }

    /**
     * Capture an approved order.
     *
     * @return array<string, mixed>
     */
    public function captureOrder(string $orderId): array
    {
        $response = Http::withToken($this->accessToken())->acceptJson()
            ->post($this->url("/v2/checkout/orders/{$orderId}/capture"));

        $this->log(null, 'capture_order', ['id' => $orderId], $response->json(), $response->status());

        if (! $response->successful()) {
            throw new RuntimeException('PayPal capture failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Fetch an order's current state (used to decide capture vs. already-captured).
     *
     * @return array<string, mixed>
     */
    public function getOrder(string $orderId): array
    {
        $response = Http::withToken($this->accessToken())->acceptJson()
            ->get($this->url("/v2/checkout/orders/{$orderId}"));

        $this->log(null, 'get_order', ['id' => $orderId], $response->json(), $response->status());

        if (! $response->successful()) {
            throw new RuntimeException('PayPal get order failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Verify a webhook notification via PayPal's verify-webhook-signature API.
     *
     * @param  array<string, ?string>  $headers  transmission headers (lower-cased keys)
     * @param  array<string, mixed>  $event  the raw event body
     */
    public function verifyWebhookSignature(array $headers, array $event): bool
    {
        $webhookId = $this->config->credential('paypal', 'webhook_id');
        if (! filled($webhookId)) {
            return false;
        }

        $payload = [
            'auth_algo'         => $headers['paypal-auth-algo'] ?? null,
            'cert_url'          => $headers['paypal-cert-url'] ?? null,
            'transmission_id'   => $headers['paypal-transmission-id'] ?? null,
            'transmission_sig'  => $headers['paypal-transmission-sig'] ?? null,
            'transmission_time' => $headers['paypal-transmission-time'] ?? null,
            'webhook_id'        => $webhookId,
            'webhook_event'     => $event,
        ];

        $response = Http::withToken($this->accessToken())->acceptJson()
            ->post($this->url('/v1/notifications/verify-webhook-signature'), $payload);

        $this->log(null, 'verify_webhook', ['transmission_id' => $payload['transmission_id']], $response->json(), $response->status());

        return ($response->json('verification_status') ?? '') === 'SUCCESS';
    }

    /**
     * Refund a captured payment (full or partial).
     *
     * @return array<string, mixed>
     */
    public function refund(string $captureId, float $amount, string $currency): array
    {
        $payload = ['amount' => [
            'currency_code' => strtoupper($currency),
            'value'         => $this->formatAmount($amount, $currency),
        ]];

        $response = Http::withToken($this->accessToken())->acceptJson()
            ->post($this->url("/v2/payments/captures/{$captureId}/refund"), $payload);

        $this->log(null, 'refund', $payload, $response->json(), $response->status());

        if (! $response->successful()) {
            throw new RuntimeException('PayPal refund failed: ' . $response->body());
        }

        return $response->json();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function accessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId(), $this->clientSecret())->asForm()
            ->post($this->url('/v1/oauth2/token'), ['grant_type' => 'client_credentials']);

        if (! $response->successful() || empty($response->json('access_token'))) {
            throw new RuntimeException('PayPal token request failed: ' . $response->body());
        }

        return $response->json('access_token');
    }

    private function url(string $path): string
    {
        $mode = strtolower((string) $this->config->credential('paypal', 'mode'));
        $base = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        return $base . $path;
    }

    private function formatAmount(float $amount, string $currency): string
    {
        $decimals = in_array(strtoupper($currency), self::ZERO_DECIMAL, true) ? 0 : 2;

        return number_format($amount, $decimals, '.', '');
    }

    private function clientId(): string
    {
        $id = $this->config->credential('paypal', 'client_id');
        if (! filled($id)) {
            throw new RuntimeException('PayPal client ID is not configured.');
        }

        return $id;
    }

    private function clientSecret(): string
    {
        $secret = $this->config->credential('paypal', 'client_secret');
        if (! filled($secret)) {
            throw new RuntimeException('PayPal client secret is not configured.');
        }

        return $secret;
    }

    private function log(?int $paymentId, string $action, array $payload, ?array $response, int $httpStatus): void
    {
        PaymentGatewayLog::create([
            'school_id'  => $this->config->school_id,
            'payment_id' => $paymentId,
            'gateway'    => 'paypal',
            'action'     => $action,
            'payload'    => $payload,
            'response'   => $response ?? [],
            'status'     => (string) $httpStatus,
        ]);
    }
}
