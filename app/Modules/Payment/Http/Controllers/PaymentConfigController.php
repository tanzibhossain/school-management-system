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
            [
                'invoice_prefix'    => 'INV',
                'invoice_last_seq'  => 0,
                'receipt_prefix'    => 'REC',
                'receipt_last_seq'  => 0,
                'bkash_fee_pct'     => 1.50,
                'sslcommerz_fee_pct'=> 2.00,
                'bounce_fee_amount' => 0.00,
            ],
        );

        return new PaymentConfigResource($config);
    }

    public function update(UpdatePaymentConfigRequest $request): PaymentConfigResource
    {
        $config = PaymentConfig::firstOrCreate(['school_id' => app('current_school_id')]);
        $config->update($request->validated());

        return new PaymentConfigResource($config->fresh());
    }
}
