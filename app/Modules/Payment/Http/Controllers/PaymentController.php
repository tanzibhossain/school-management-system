<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Payment\Http\Requests\InitiateGatewayRequest;
use App\Modules\Payment\Http\Requests\RecordPaymentRequest;
use App\Modules\Payment\Http\Resources\PaymentResource;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $service) {}

    public function show(int $id): PaymentResource
    {
        $payment = Payment::where('school_id', app('current_school_id'))
            ->with('invoice')
            ->findOrFail($id);

        return new PaymentResource($payment);
    }

    /** Record a manual payment (cash / cheque / bank_transfer / waiver). */
    public function record(RecordPaymentRequest $request, int $invoiceId): JsonResponse
    {
        $invoice = Invoice::where('school_id', app('current_school_id'))->findOrFail($invoiceId);

        try {
            $payment = $this->service->recordManual($invoice, $request->validated(), $request->user()->id);

            return (new PaymentResource($payment))->response()->setStatusCode(201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /** Initiate a bKash payment — returns bkashURL for frontend redirect. */
    public function initiateBkash(InitiateGatewayRequest $request, int $invoiceId): JsonResponse
    {
        $invoice = Invoice::where('school_id', app('current_school_id'))->findOrFail($invoiceId);
        $result  = $this->service->initiateBkash($invoice, $request->validated()['callback_url']);

        return response()->json($result);
    }

    /** Initiate an SSLCommerz session — returns GatewayPageURL for frontend redirect. */
    public function initiateSslcommerz(InitiateGatewayRequest $request, int $invoiceId): JsonResponse
    {
        $invoice = Invoice::where('school_id', app('current_school_id'))->findOrFail($invoiceId);
        $data    = $request->validated();

        $result = $this->service->initiateSslcommerz(
            $invoice,
            $data['success_url'],
            $data['fail_url'],
            $data['cancel_url'],
            $data['ipn_url'],
        );

        return response()->json($result);
    }
}
