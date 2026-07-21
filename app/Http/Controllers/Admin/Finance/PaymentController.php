<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(): View
    {
        $payments = Payment::where('school_id', app('current_school_id'))
            ->with(['invoice:id,invoice_number', 'invoice.student:id,name'])
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('admin.finance.payments.index', compact('payments'));
    }

    public function store(Request $request, int $invoiceId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $invoice = Invoice::where('school_id', $schoolId)->findOrFail($invoiceId);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank_transfer,cheque'],
            'note' => ['nullable', 'string', 'max:255'],
            'cheque_number' => ['nullable', 'required_if:method,cheque', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'cheque_date' => ['nullable', 'date'],
        ]);

        $data = [
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'note' => $validated['note'] ?? null,
        ];

        if ($validated['method'] === 'cheque') {
            $data['cheque_number'] = $validated['cheque_number'];
            $data['bank_name'] = $validated['bank_name'] ?? null;
            $data['cheque_date'] = $validated['cheque_date'] ?? null;
        }

        try {
            $this->payments->recordManual($invoice, $data, (int) auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Payment Recorded.'));
    }
}
