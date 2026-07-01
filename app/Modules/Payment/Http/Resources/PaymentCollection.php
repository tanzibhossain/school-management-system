<?php

namespace App\Modules\Payment\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentCollection extends ResourceCollection
{
    public $collects = PaymentResource::class;
}
