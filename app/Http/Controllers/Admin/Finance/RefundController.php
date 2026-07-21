<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\Refund;
use App\Modules\Payment\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use RuntimeException;

class RefundController extends Controller
{
    public function __construct(private readonly RefundService $refunds) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        $refunds = Refund::where('school_id', $schoolId)
            ->with(['payment:id,receipt_number,amount,method'])
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        // Refundable payments: not reversed, and (manual OR successful gateway)
        $payments = Payment::where('school_id', $schoolId)
            ->where('is_reversed', false)
            ->with('invoice:id,invoice_number')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('admin.finance.refunds.index', compact('refunds', 'payments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $request->validate([
            'payment_id' => ['required', 'integer', "exists:payments,id,school_id,{$schoolId}"],
        ]);
        $payment = Payment::where('school_id', $schoolId)->findOrFail($request->integer('payment_id'));

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.(float) $payment->amount],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->refunds->request($payment, (float) $data['amount'], (int) auth()->id(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Refund Requested.'));
    }
}
