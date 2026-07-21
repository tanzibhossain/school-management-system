<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Payment\Http\Requests\UpdatePaymentConfigRequest;
use App\Modules\Payment\Http\Resources\PaymentConfigResource;
use App\Modules\Payment\Models\PaymentConfig;
use Illuminate\Routing\Controller;

class PaymentConfigController extends Controller
{
    public function show(): PaymentConfigResource
    {
        $config = PaymentConfig::firstOrCreate(
            ['school_id' => app('current_school_id')],
            ['invoice_prefix' => 'INV', 'receipt_prefix' => 'REC'],
        );

        return new PaymentConfigResource($config);
    }

    public function update(UpdatePaymentConfigRequest $request): PaymentConfigResource
    {
        $config = PaymentConfig::firstOrCreate(['school_id' => app('current_school_id')]);
        $data = $request->validated();

        foreach (['payment_mode', 'invoice_prefix', 'receipt_prefix', 'bounce_fee_amount'] as $field) {
            if (array_key_exists($field, $data)) {
                $config->{$field} = $data[$field];
            }
        }

        // Generic gateway store — only slugs available to this school's country are
        // applied; a blank credential keeps the stored value; credentials never leave.
        if (isset($data['gateways'])) {
            $available = array_keys($config->availableGatewayDefs());
            $store = $config->gateways ?? [];

            foreach ($data['gateways'] as $slug => $gw) {
                if (! in_array($slug, $available, true)) {
                    continue;
                }
                $creds = $store[$slug]['credentials'] ?? [];
                foreach (($gw['credentials'] ?? []) as $key => $value) {
                    if (filled($value)) {
                        $creds[$key] = $value;
                    }
                }
                $feePct = $gw['fee_pct'] ?? null;
                $store[$slug] = [
                    'enabled' => (bool) ($gw['enabled'] ?? ($store[$slug]['enabled'] ?? false)),
                    'credentials' => $creds,
                    'fee_pct' => filled($feePct) ? (float) $feePct : ($store[$slug]['fee_pct'] ?? 0.0),
                ];
            }
            $config->gateways = $store;
        }

        $config->save();

        return new PaymentConfigResource($config->fresh());
    }
}
