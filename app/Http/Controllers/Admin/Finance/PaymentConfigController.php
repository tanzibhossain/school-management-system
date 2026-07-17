<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Modules\Payment\Models\PaymentConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentConfigController extends Controller
{
    public function edit(): View
    {
        $config = PaymentConfig::firstOrCreate(['school_id' => app('current_school_id')]);

        return view('admin.finance.payment-config.edit', [
            'config'   => $config,
            'gateways' => $config->availableGatewayDefs(), // gateways for the school's country
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $config = PaymentConfig::firstOrCreate(['school_id' => app('current_school_id')]);
        $gateways = $config->availableGatewayDefs();

        // Base rules + a rule per available gateway's flag and fields.
        $rules = [
            'payment_mode'       => ['required', 'in:offline,online,both'],
            'invoice_prefix'     => ['nullable', 'string', 'max:20'],
            'receipt_prefix'     => ['nullable', 'string', 'max:20'],
            'bkash_fee_pct'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sslcommerz_fee_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bounce_fee_amount'  => ['nullable', 'numeric', 'min:0'],
        ];
        foreach ($gateways as $def) {
            $rules[$def['enabled_field']] = ['nullable', 'boolean'];
            foreach (array_keys($def['fields']) as $field) {
                $rules[$field] = ['nullable', 'string', 'max:255'];
            }
        }
        $data = $request->validate($rules);

        // Enabling a gateway requires its credentials — unless already stored.
        if (in_array($data['payment_mode'], ['online', 'both'], true)) {
            $missing = [];
            foreach ($gateways as $def) {
                if (! $request->boolean($def['enabled_field'])) {
                    continue;
                }
                foreach ($def['fields'] as $field => $meta) {
                    if (! empty($meta['required']) && ! filled($data[$field] ?? null) && ! filled($config->{$field})) {
                        $missing[$field] = ["{$def['label']} {$meta['label']} is required to enable this gateway."];
                    }
                }
            }
            if ($missing) {
                throw ValidationException::withMessages($missing);
            }
        }

        // Mode + gateway switches (for the country's gateways) always apply.
        $config->payment_mode = $data['payment_mode'];
        foreach ($gateways as $def) {
            $config->{$def['enabled_field']} = $request->boolean($def['enabled_field']);
        }

        // Everything else only overwrites when a value is actually supplied — a
        // blank/absent field never nulls a NOT-NULL column or wipes a stored key.
        $optional = ['invoice_prefix', 'receipt_prefix', 'bkash_fee_pct', 'sslcommerz_fee_pct', 'bounce_fee_amount'];
        foreach ($gateways as $def) {
            $optional = array_merge($optional, array_keys($def['fields']));
        }
        foreach (array_unique($optional) as $field) {
            if (filled($data[$field] ?? null)) {
                $config->{$field} = $data[$field];
            }
        }

        $config->save();

        return back()->with('status', 'Payment configuration saved.');
    }
}
