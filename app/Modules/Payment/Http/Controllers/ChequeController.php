<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Payment\Http\Requests\BouncePaymentRequest;
use App\Modules\Payment\Http\Resources\PaymentCollection;
use App\Modules\Payment\Http\Resources\PaymentResource;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Repositories\PaymentRepository;
use App\Modules\Payment\Services\ChequeService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChequeController extends Controller
{
    public function __construct(
        private readonly ChequeService $service,
        private readonly PaymentRepository $repository,
    ) {}

    /** List all cheques with status = submitted (pending clearance). */
    public function index(Request $request): PaymentCollection
    {
        $payments = $this->repository->pendingCheques(
            app('current_school_id'),
            $request->integer('per_page', 30),
        );

        return new PaymentCollection($payments);
    }

    public function clear(Request $request, int $id): PaymentResource
    {
        $payment = Payment::where('school_id', app('current_school_id'))
            ->where('method', 'cheque')
            ->findOrFail($id);

        return new PaymentResource($this->service->clear($payment));
    }

    public function bounce(BouncePaymentRequest $request, int $id): PaymentResource
    {
        $payment = Payment::where('school_id', app('current_school_id'))
            ->where('method', 'cheque')
            ->findOrFail($id);

        $data = $request->validated();
        $this->service->bounce($payment, $request->user()->id, $data['bounce_fee'] ?? null);

        return new PaymentResource($payment->fresh());
    }
}
