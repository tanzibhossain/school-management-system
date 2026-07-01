<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Payment\Http\Requests\RequestRefundRequest;
use App\Modules\Payment\Http\Resources\RefundResource;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\Refund;
use App\Modules\Payment\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class RefundController extends Controller
{
    public function __construct(private readonly RefundService $service) {}

    public function index(Request $request): JsonResponse
    {
        $refunds = Refund::where('school_id', app('current_school_id'))
            ->with('payment')
            ->latest()
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'data' => RefundResource::collection($refunds->items()),
            'meta' => [
                'total'        => $refunds->total(),
                'per_page'     => $refunds->perPage(),
                'current_page' => $refunds->currentPage(),
                'last_page'    => $refunds->lastPage(),
            ],
        ]);
    }

    public function show(int $id): RefundResource
    {
        $refund = Refund::where('school_id', app('current_school_id'))
            ->with('payment')
            ->findOrFail($id);

        return new RefundResource($refund);
    }

    public function request(RequestRefundRequest $request, int $paymentId): JsonResponse
    {
        $payment = Payment::where('school_id', app('current_school_id'))
            ->where('is_reversed', false)
            ->findOrFail($paymentId);

        $data   = $request->validated();
        $refund = $this->service->request($payment, (float) $data['amount'], $request->user()->id, $data['note'] ?? null);

        return (new RefundResource($refund))->response()->setStatusCode(201);
    }
}
