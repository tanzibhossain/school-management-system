<?php

namespace App\Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Payment\Models\PaymentConfig */
class PaymentConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // One generic shape per gateway available to the school's country.
        // Credentials are never exposed — only whether each required field is set.
        $gateways = [];
        foreach ($this->availableGatewayDefs() as $slug => $def) {
            $configured = collect($def['fields'])
                ->filter(fn ($meta) => ! empty($meta['required']))
                ->keys()
                ->every(fn ($field) => filled($this->credential($slug, $field)));

            $gateways[$slug] = [
                'label'      => $def['label'],
                'enabled'    => $this->gatewayEnabled($slug),
                'configured' => $configured,
                'fee_pct'    => $this->feePct($slug),
                'currencies' => $def['currencies'],
            ];
        }

        return [
            'payment_mode'      => $this->payment_mode,
            'invoice_prefix'    => $this->invoice_prefix,
            'invoice_last_seq'  => $this->invoice_last_seq,
            'receipt_prefix'    => $this->receipt_prefix,
            'receipt_last_seq'  => $this->receipt_last_seq,
            'bounce_fee_amount' => $this->bounce_fee_amount,
            'gateways'          => $gateways,
        ];
    }
}
