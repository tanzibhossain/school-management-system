<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Payment\Http\Resources\PaymentResource;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class SslcommerzWebhookController extends Controller
{
    public function __construct(private readonly PaymentService $service) {}

    /**
     * IPN (Instant Payment Notification) — server-to-server POST from SSLCommerz.
     * We identify the school via tran_id (= invoice_number).
     */
    public function ipn(Request $request): JsonResponse
    {
        $tranId = $request->post('tran_id');
        $valId = $request->post('val_id');
        $status = $request->post('status');

        // SSLCommerz IPN can post status 'VALID' or 'VALIDATED'
        if (! in_array($status, ['VALID', 'VALIDATED'], true) || ! $tranId || ! $valId) {
            Log::warning('SSLCommerz IPN invalid', $request->post());

            return response()->json(['message' => 'Ignored.']);
        }

        // Resolve invoice + school from tran_id (= invoice_number)
        $invoice = Invoice::where('invoice_number', $tranId)->first();

        if (! $invoice) {
            Log::error("SSLCommerz IPN: invoice not found for tran_id={$tranId}");

            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        $payment = $this->service->verifySslcommerz($invoice, $valId);

        return response()->json(['message' => $payment ? 'OK' : 'Already recorded.']);
    }

    /** Browser success redirect from SSLCommerz. */
    public function success(Request $request): JsonResponse
    {
        return $this->handleBrowserRedirect($request, 'VALID');
    }

    /** Browser fail redirect. */
    public function fail(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Payment failed.'], 422);
    }

    /** Browser cancel redirect. */
    public function cancel(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Payment cancelled.'], 422);
    }

    private function handleBrowserRedirect(Request $request, string $expectedStatus): JsonResponse
    {
        $tranId = $request->post('tran_id') ?? $request->get('tran_id');
        $valId = $request->post('val_id') ?? $request->get('val_id');
        $status = $request->post('status') ?? $request->get('status');

        if ($status !== $expectedStatus || ! $tranId || ! $valId) {
            return response()->json(['message' => 'Payment verification failed.'], 422);
        }

        $invoice = Invoice::where('invoice_number', $tranId)->first();

        if (! $invoice) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        $payment = $this->service->verifySslcommerz($invoice, $valId);

        if (! $payment) {
            return response()->json(['message' => 'Payment already recorded or verification failed.'], 422);
        }

        return response()->json(['message' => 'Payment successful.', 'data' => new PaymentResource($payment)]);
    }
}
