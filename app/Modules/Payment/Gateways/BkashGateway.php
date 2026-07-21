<?php

namespace App\Modules\Payment\Gateways;

use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\Payment\Models\PaymentGatewayLog;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * bKash Tokenized Checkout API (v1.2.0-4).
 *
 * Token is generated fresh per transaction — avoids stale-token errors.
 */
class BkashGateway
{
    /** bKash processes BDT only. */
    public const SUPPORTED_CURRENCIES = ['BDT'];

    private PaymentConfig $config;

    public function __construct(PaymentConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Grant a short-lived ID token from bKash.
     */
    public function grantToken(): string
    {
        $response = Http::withHeaders([
            'username' => $this->config->credential('bkash', 'username'),
            'password' => $this->config->credential('bkash', 'password'),
            'Content-Type' => 'application/json',
        ])->post($this->url('checkout/token/grant'), [
            'app_key' => $this->config->credential('bkash', 'app_key'),
            'app_secret' => $this->config->credential('bkash', 'app_secret'),
        ]);

        $this->log(null, 'grant_token', [], $response->json(), $response->status());

        if (! $response->successful() || empty($response->json('id_token'))) {
            throw new RuntimeException('bKash token grant failed: '.$response->body());
        }

        return $response->json('id_token');
    }

    /**
     * Create a bKash payment session.
     *
     * @return array{ paymentID: string, bkashURL: string }
     */
    public function createPayment(string $token, string $invoiceNumber, float $amount, string $callbackUrl, int $studentId): array
    {
        $payload = [
            'mode' => '0011',
            'payerReference' => (string) $studentId,
            'callbackURL' => $callbackUrl,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoiceNumber,
        ];

        $response = Http::withHeaders($this->authHeaders($token))
            ->post($this->url('checkout/create'), $payload);

        $this->log(null, 'create', $payload, $response->json(), $response->status());

        if (! $response->successful() || $response->json('statusCode') !== '0000') {
            throw new RuntimeException('bKash create payment failed: '.$response->body());
        }

        return [
            'paymentID' => $response->json('paymentID'),
            'bkashURL' => $response->json('bkashURL'),
        ];
    }

    /**
     * Execute payment after customer completes on bKash app.
     *
     * @return array{ trxID: string, amount: string, transactionStatus: string }
     */
    public function executePayment(string $token, string $paymentId): array
    {
        $response = Http::withHeaders($this->authHeaders($token))
            ->post($this->url('checkout/execute'), ['paymentID' => $paymentId]);

        $this->log(null, 'execute', ['paymentID' => $paymentId], $response->json(), $response->status());

        if (! $response->successful() || $response->json('statusCode') !== '0000') {
            throw new RuntimeException('bKash execute failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Query payment status from bKash (used for verification).
     */
    public function queryPayment(string $token, string $paymentId): array
    {
        $response = Http::withHeaders($this->authHeaders($token))
            ->post($this->url('checkout/payment/status'), ['paymentID' => $paymentId]);

        $this->log(null, 'query', ['paymentID' => $paymentId], $response->json(), $response->status());

        return $response->json();
    }

    /**
     * Initiate a refund for a completed bKash transaction.
     */
    public function refund(string $token, string $paymentId, string $trxId, float $amount, string $reason): array
    {
        $payload = [
            'paymentID' => $paymentId,
            'trxID' => $trxId,
            'amount' => number_format($amount, 2, '.', ''),
            'reason' => $reason,
            'sku' => 'fee-refund',
        ];

        $response = Http::withHeaders($this->authHeaders($token))
            ->post($this->url('checkout/payment/refund'), $payload);

        $this->log(null, 'refund', $payload, $response->json(), $response->status());

        if (! $response->successful()) {
            throw new RuntimeException('bKash refund failed: '.$response->body());
        }

        return $response->json();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function url(string $path): string
    {
        return rtrim($this->config->credential('bkash', 'base_url'), '/').'/'.$path;
    }

    /** @return array<string, string> */
    private function authHeaders(string $token): array
    {
        // bKash Tokenized Checkout expects the raw id_token — no "Bearer" prefix
        return [
            'Authorization' => $token,
            'X-APP-Key' => $this->config->credential('bkash', 'app_key'),
            'Content-Type' => 'application/json',
        ];
    }

    private function log(?int $paymentId, string $action, array $payload, ?array $response, int $httpStatus): void
    {
        // Strip credentials from logs — never persist app_key, app_secret, username, password
        unset($payload['app_key'], $payload['app_secret'], $payload['username'], $payload['password']);

        PaymentGatewayLog::create([
            'school_id' => $this->config->school_id,
            'payment_id' => $paymentId,
            'gateway' => 'bkash',
            'action' => $action,
            'payload' => $payload,
            'response' => $response ?? [],
            'status' => (string) $httpStatus,
        ]);
    }
}
