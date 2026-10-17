<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Payment\Http\Resources\PaymentResource;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BkashWebhookController extends Controller
{
    public function __construct(private readonly PaymentService $service) {}

    /**
     * GET callback from bKash browser redirect.
     * bKash sends: paymentID, status, apiVersion (all query params).
     */
    public function callback(Request $request): JsonResponse
    {
        $paymentId = $request->query('paymentID');
        $status = $request->query('status');

        if ($status !== 'success' || ! $paymentId) {
            Log::warning('bKash callback failed', $request->query());

            return response()->json(['message' => 'Payment failed or cancelled.'], 422);
        }

        // Resolve school from the Redis cache set during initiation
        $cached = Cache::get("bkash_payment:{$paymentId}");

        if (! $cached) {
            Log::error("bKash callback: no cache entry for paymentID={$paymentId}");

            return response()->json(['message' => 'Payment session expired or not found.'], 404);
        }

        try {
            $payment = $this->service->executeBkash($paymentId, $cached['invoice_id'], $cached['school_id']);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['message' => 'bKash payment execution failed.'], 422);
        }

        return response()->json(['message' => 'Payment successful.', 'data' => new PaymentResource($payment)]);
    }
}
