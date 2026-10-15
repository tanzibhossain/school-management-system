<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Payment\Http\Requests\UpdatePaymentConfigRequest;
use App\Modules\Payment\Http\Resources\PaymentConfigResource;
use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\Payment\Services\PaymentConfigService;
use Illuminate\Routing\Controller;

class PaymentConfigController extends Controller
{
    public function __construct(private readonly PaymentConfigService $configs) {}

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

        // Only slugs available to this school's country are applied; a blank
        // credential keeps the stored value; an omitted `enabled` keeps the
        // stored state; credentials never leave (see PaymentConfigResource).
        if (isset($data['gateways'])) {
            $available = array_keys($config->availableGatewayDefs());
            $updates = [];
            foreach ($data['gateways'] as $slug => $gw) {
                if (in_array($slug, $available, true)) {
                    $updates[$slug] = [
                        'enabled' => isset($gw['enabled']) ? (bool) $gw['enabled'] : null,
                        'credentials' => (array) ($gw['credentials'] ?? []),
                        'fee_pct' => $gw['fee_pct'] ?? null,
                    ];
                }
            }
            $this->configs->applyGatewayUpdates($config, $updates);
        }

        $config->save();

        return new PaymentConfigResource($config->fresh());
    }
}
