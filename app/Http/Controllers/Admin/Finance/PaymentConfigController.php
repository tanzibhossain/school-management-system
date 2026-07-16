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
            'payment_mode'        => ['required', 'in:offline,online,both'],
            'bkash_enabled'       => ['nullable', 'boolean'],
            'sslcommerz_enabled'  => ['nullable', 'boolean'],
            'invoice_prefix'      => ['nullable', 'string', 'max:20'],
            'receipt_prefix'      => ['nullable', 'string', 'max:20'],
            'bkash_fee_pct'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sslcommerz_fee_pct'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bounce_fee_amount'   => ['nullable', 'numeric', 'min:0'],
            // Gateway credentials (kept as-is when the field is left blank).
            'bkash_app_key'       => ['nullable', 'string', 'max:255'],
            'bkash_app_secret'    => ['nullable', 'string', 'max:255'],
            'bkash_username'      => ['nullable', 'string', 'max:255'],
            'bkash_password'      => ['nullable', 'string', 'max:255'],
            'bkash_base_url'      => ['nullable', 'string', 'max:255'],
            'sslcommerz_store_id'   => ['nullable', 'string', 'max:255'],
            'sslcommerz_store_pass' => ['nullable', 'string', 'max:255'],
            'sslcommerz_base_url'   => ['nullable', 'string', 'max:255'],
        ]);

        // Mode + gateway switches always apply.
        $config->payment_mode = $data['payment_mode'];
        $config->bkash_enabled = $request->boolean('bkash_enabled');
        $config->sslcommerz_enabled = $request->boolean('sslcommerz_enabled');

        // Everything else only overwrites when a value is actually supplied — a
        // blank/absent field never nulls a NOT-NULL column or wipes a stored key.
        $optional = [
            'invoice_prefix', 'receipt_prefix', 'bkash_fee_pct', 'sslcommerz_fee_pct',
            'bounce_fee_amount', 'bkash_base_url', 'sslcommerz_base_url',
            'bkash_app_key', 'bkash_app_secret', 'bkash_username', 'bkash_password',
            'sslcommerz_store_id', 'sslcommerz_store_pass',
        ];
        foreach ($optional as $field) {
            if (filled($data[$field] ?? null)) {
                $config->{$field} = $data[$field];
            }
        }

        $config->save();

        return back()->with('status', 'Payment configuration saved.');
    }
}
