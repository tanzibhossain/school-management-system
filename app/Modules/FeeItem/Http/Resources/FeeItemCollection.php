<?php

namespace App\Modules\FeeItem\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class FeeItemCollection extends ResourceCollection
{
    public $collects = FeeItemResource::class;
}
