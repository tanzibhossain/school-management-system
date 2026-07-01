<?php

namespace App\Modules\Staff\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StaffCollection extends ResourceCollection
{
    public $collects = StaffListResource::class;
}
