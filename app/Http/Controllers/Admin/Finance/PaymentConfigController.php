<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\Payment\Services\PaymentConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PaymentConfigController extends Controller
{
    public function __construct(private readonly PaymentConfigService $configs) {}

    public function edit(): View
    {
        $config = PaymentConfig::firstOrCreate(['school_id' => app('current_school_id')]);

        return view('admin.finance.payment-config.edit', [
            'config' => $config,
            'gateways' => $config->availableGatewayDefs(), // gateways for the school's country
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $config = PaymentConfig::firstOrCreate(['school_id' => app('current_school_id')]);

        $data = $request->validate([
            'payment_mode' => ['required', 'in:offline,online,both'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'receipt_prefix' => ['nullable', 'string', 'max:20'],
            'bounce_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'gw' => ['array'],
            'gw.*' => ['array'],
            'gw.*.fee_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        // Throws a ValidationException if an enabled gateway lacks a required credential.
        $this->configs->updateFromAdminForm($config, $data, (array) $request->input('gw', []));

        return back()->with('status', __('Payment Configuration Saved.'));
    }
}
