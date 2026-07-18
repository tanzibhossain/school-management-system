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

        $data = $request->validate([
            'payment_mode'      => ['required', 'in:offline,online,both'],
            'invoice_prefix'    => ['nullable', 'string', 'max:20'],
            'receipt_prefix'    => ['nullable', 'string', 'max:20'],
            'bounce_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'gw'                => ['array'],
            'gw.*'              => ['array'],
            'gw.*.fee_pct'      => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        // Enabling a gateway requires its credentials — unless already stored.
        if (in_array($data['payment_mode'], ['online', 'both'], true)) {
            $missing = [];
            foreach ($gateways as $slug => $def) {
                if (! $request->boolean("gw.{$slug}.enabled")) {
                    continue;
                }
                foreach ($def['fields'] as $field => $meta) {
                    if (empty($meta['required'])) {
                        continue;
                    }
                    $submitted = $request->input("gw.{$slug}.cred.{$field}");
                    if (! filled($submitted) && ! filled($config->credential($slug, $field))) {
                        $missing["gw.{$slug}.cred.{$field}"] = ["{$def['label']} {$meta['label']} is required to enable this gateway."];
                    }
                }
            }
            if ($missing) {
                throw ValidationException::withMessages($missing);
            }
        }

        // Non-gateway settings (never null a NOT-NULL column).
        $config->payment_mode = $data['payment_mode'];
        foreach (['invoice_prefix', 'receipt_prefix', 'bounce_fee_amount'] as $field) {
            if (filled($data[$field] ?? null)) {
                $config->{$field} = $data[$field];
            }
        }

        // Build the generic gateway store — a blank field keeps the saved value.
        $store = $config->gateways ?? [];
        foreach ($gateways as $slug => $def) {
            $creds = $store[$slug]['credentials'] ?? [];
            foreach (array_keys($def['fields']) as $field) {
                $value = $request->input("gw.{$slug}.cred.{$field}");
                if (filled($value)) {
                    $creds[$field] = $value;
                }
            }
            $feePct = $request->input("gw.{$slug}.fee_pct");
            $store[$slug] = [
                'enabled'     => $request->boolean("gw.{$slug}.enabled"),
                'credentials' => $creds,
                'fee_pct'     => filled($feePct) ? (float) $feePct : ($store[$slug]['fee_pct'] ?? 0.0),
            ];
        }
        $config->gateways = $store;

        $config->save();

        return back()->with('status', 'Payment configuration saved.');
    }
}
