<?php

namespace App\Modules\Payment\Http\Resources;

use App\Modules\Payment\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InvoiceItem */
class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fee_item_id' => $this->fee_item_id,
            'name' => $this->name,
            'amount' => $this->amount,
            'discount_amount' => $this->discount_amount,
            'net_amount' => $this->net_amount,
        ];
    }
}
