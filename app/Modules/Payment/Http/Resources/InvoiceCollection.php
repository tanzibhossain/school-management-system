<?php

namespace App\Modules\Payment\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InvoiceCollection extends ResourceCollection
{
    public $collects = InvoiceResource::class;
}
