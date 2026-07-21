<?php

namespace App\Http\Controllers\Payment;

use App\Modules\Payment\Gateways\PayPalGateway;
use App\Modules\Payment\Gateways\StripeGateway;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Server-to-server gateway webhooks — the authoritative confirmation so an invoice
 * settles even if the payer never returns to the browser. Public + CSRF-exempt
 * (see bootstrap/app.php); the signature is the trust boundary. Recording reuses
 * the same idempotent PaymentService::verify* the browser return uses.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly PaymentService $service) {}

    /** POST /payments/webhook/stripe — Stripe Checkout events. */
    public function stripe(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $event = json_decode($payload, true) ?: [];

        if (($event['type'] ?? '') !== 'checkout.session.completed') {
            return response()->json(['ignored' => true]); // ack unhandled events
        }

        $session = $event['data']['object'] ?? [];
        $sessionId = data_get($session, 'id');
        $schoolId = (int) data_get($session, 'metadata.school_id');
        $invoiceId = (int) data_get($session, 'metadata.invoice_id');

        if (! $sessionId || ! $schoolId || ! $invoiceId) {
            return response()->json(['message' => 'Missing metadata.'], 422);
        }

        $config = PaymentConfig::where('school_id', $schoolId)->first();
        if (! $config) {
            return response()->json(['message' => 'No payment configuration.'], 404);
        }

        if (! (new StripeGateway($config))->verifyWebhookSignature($payload, $request->header('Stripe-Signature'))) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        return $this->record(fn () => $this->service->verifyStripe($sessionId, $invoiceId, $schoolId));
    }

    /** POST /payments/webhook/paypal — PayPal order/capture events. */
    public function paypal(Request $request): JsonResponse
    {
        $event = $request->json()->all();

        if (($event['event_type'] ?? '') !== 'CHECKOUT.ORDER.APPROVED') {
            return response()->json(['ignored' => true]); // capture already records the rest
        }

        $resource = $event['resource'] ?? [];
        $orderId = data_get($resource, 'id');
        $customId = data_get($resource, 'purchase_units.0.custom_id') ?? data_get($resource, 'custom_id');

        if (! $orderId || ! $customId) {
            return response()->json(['message' => 'Missing order data.'], 422);
        }

        $invoice = Invoice::where('invoice_number', $customId)->first();
        if (! $invoice) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        $config = PaymentConfig::where('school_id', $invoice->school_id)->first();
        if (! $config) {
            return response()->json(['message' => 'No payment configuration.'], 404);
        }

        $headers = [
            'paypal-auth-algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'paypal-cert-url' => $request->header('PAYPAL-CERT-URL'),
            'paypal-transmission-id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'paypal-transmission-sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'paypal-transmission-time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
        ];

        if (! (new PayPalGateway($config))->verifyWebhookSignature($headers, $event)) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        return $this->record(fn () => $this->service->verifyPayPal($orderId, $invoice->id, $invoice->school_id));
    }

    /**
     * Run the idempotent recording and map failures to a 500 so the gateway retries
     * (a later retry succeeds once the transient issue clears; recording is idempotent).
     */
    private function record(callable $verify): JsonResponse
    {
        try {
            $verify();

            return response()->json(['received' => true]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Processing failed; will retry.'], 500);
        }
    }
}
