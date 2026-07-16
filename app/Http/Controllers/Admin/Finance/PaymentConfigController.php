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

        // Non-secret settings always update.
        $config->fill([
            'payment_mode'       => $data['payment_mode'],
            'bkash_enabled'      => $request->boolean('bkash_enabled'),
            'sslcommerz_enabled' => $request->boolean('sslcommerz_enabled'),
            'invoice_prefix'     => $data['invoice_prefix'] ?? $config->invoice_prefix,
            'receipt_prefix'     => $data['receipt_prefix'] ?? $config->receipt_prefix,
            'bkash_fee_pct'      => $data['bkash_fee_pct'] ?? $config->bkash_fee_pct,
            'sslcommerz_fee_pct' => $data['sslcommerz_fee_pct'] ?? $config->sslcommerz_fee_pct,
            'bounce_fee_amount'  => $data['bounce_fee_amount'] ?? $config->bounce_fee_amount,
            'bkash_base_url'     => $data['bkash_base_url'] ?? $config->bkash_base_url,
            'sslcommerz_base_url' => $data['sslcommerz_base_url'] ?? $config->sslcommerz_base_url,
        ]);

        // Secret credentials only overwrite when a new value is supplied, so a
        // blank field never wipes stored keys.
        foreach (['bkash_app_key', 'bkash_app_secret', 'bkash_username', 'bkash_password',
            'sslcommerz_store_id', 'sslcommerz_store_pass'] as $secret) {
            if (filled($data[$secret] ?? null)) {
                $config->{$secret} = $data[$secret];
            }
        }

        $config->save();

        return back()->with('status', 'Payment configuration saved.');
    }
}
