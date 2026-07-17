<?php

namespace App\Modules\Payment\Gateways;

use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\Payment\Models\PaymentGatewayLog;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * SSLCommerz Payment Gateway integration.
 *
 * Uses invoice_number as tran_id (unique per school).
 * Handles session init, IPN validation, and refund.
 */
class SslcommerzGateway
{
    /** SSLCommerz settles in BDT (multi-currency display exists, settlement is BDT). */
    public const SUPPORTED_CURRENCIES = ['BDT'];

    private PaymentConfig $config;

    public function __construct(PaymentConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Initialise a payment session with SSLCommerz.
     *
     * @return array{ GatewayPageURL: string, sessionkey: string }
     */
    public function initSession(
        string $invoiceNumber,
        float $amount,
        int $studentId,
        string $successUrl,
        string $failUrl,
        string $cancelUrl,
        string $ipnUrl,
    ): array {
        $payload = [
            'store_id'          => $this->config->credential('sslcommerz', 'store_id'),
            'store_passwd'      => $this->config->credential('sslcommerz', 'store_pass'),
            'total_amount'      => $amount,
            'currency'          => 'BDT',
            'tran_id'           => $invoiceNumber,
            'success_url'       => $successUrl,
            'fail_url'          => $failUrl,
            'cancel_url'        => $cancelUrl,
            'ipn_url'           => $ipnUrl,
            'product_name'      => 'School Fee',
            'product_category'  => 'Education',
            'product_profile'   => 'general',
            'cus_name'          => "Student#{$studentId}",
            'cus_email'         => 'noreply@school.edu',
            'cus_phone'         => '01700000000',
            'shipping_method'   => 'NO',
            'num_of_item'       => 1,          // correct field name per SSLCommerz SDK
        ];

        $response = Http::asForm()
            ->post($this->url('gwprocess/v4/api.php'), $payload);

        $this->log(null, 'init_session', $payload, $response->json(), $response->status());

        // Sandbox returns 'success' (lowercase), production returns 'SUCCESS'
        if (! $response->successful() || strtoupper($response->json('status', '')) !== 'SUCCESS') {
            throw new RuntimeException('SSLCommerz session init failed: ' . $response->body());
        }

        return [
            'GatewayPageURL' => $response->json('GatewayPageURL'),
            'sessionkey'     => $response->json('sessionkey'),
        ];
    }

    /**
     * Validate a payment by val_id (called after IPN/success redirect).
     */
    public function validatePayment(string $valId): array
    {
        $response = Http::get($this->url('validator/api/validationserverAPI.php'), [
            'val_id'       => $valId,
            'store_id'     => $this->config->credential('sslcommerz', 'store_id'),
            'store_passwd' => $this->config->credential('sslcommerz', 'store_pass'),
            'v'            => 1,       // required per SSLCommerz validation API
            'format'       => 'json',
        ]);

        $this->log(null, 'verify', ['val_id' => $valId], $response->json(), $response->status());

        if (! $response->successful()) {
            throw new RuntimeException('SSLCommerz validation failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Initiate a refund via SSLCommerz.
     */
    public function refund(string $bankTranId, float $amount, string $remarks, string $refeId): array
    {
        $payload = [
            'store_id'        => $this->config->credential('sslcommerz', 'store_id'),
            'store_passwd'    => $this->config->credential('sslcommerz', 'store_pass'),
            'bank_tran_id'    => $bankTranId,
            'refund_amount'   => $amount,
            'refund_remarks'  => $remarks,
            'refe_id'         => $refeId,
            'v1'              => '',
        ];

        $response = Http::asForm()
            ->post($this->url('validator/api/merchantTransIDvalidationAPI.php'), $payload);

        $this->log(null, 'refund', $payload, $response->json(), $response->status());

        if (! $response->successful()) {
            throw new RuntimeException('SSLCommerz refund failed: ' . $response->body());
        }

        return $response->json();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function url(string $path): string
    {
        return rtrim($this->config->credential('sslcommerz', 'base_url'), '/') . '/' . $path;
    }

    private function log(?int $paymentId, string $action, array $payload, ?array $response, int $httpStatus): void
    {
        // Strip credentials from logged payload
        unset($payload['store_passwd'], $payload['store_id']);

        PaymentGatewayLog::create([
            'school_id'  => $this->config->school_id,
            'payment_id' => $paymentId,
            'gateway'    => 'sslcommerz',
            'action'     => $action,
            'payload'    => $payload,
            'response'   => $response ?? [],
            'status'     => (string) $httpStatus,
        ]);
    }
}
