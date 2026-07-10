<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Modules\Payment\Models\PaymentConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PaymentConfigController extends Controller
{
    public function edit(): View
    {
        $config = PaymentConfig::firstOrCreate(['school_id' => app('current_school_id')]);

        return view('admin.finance.payment-config.edit', compact('config'));
    }

    public function update(Request $request): RedirectResponse
    {
        $config = PaymentConfig::firstOrCreate(['school_id' => app('current_school_id')]);

        $data = $request->validate([
            'invoice_prefix'     => ['nullable', 'string', 'max:20'],
            'receipt_prefix'     => ['nullable', 'string', 'max:20'],
            'bkash_fee_pct'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sslcommerz_fee_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bounce_fee_amount'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $config->update($data);

        return back()->with('status', 'Payment configuration saved.');
    }
}
