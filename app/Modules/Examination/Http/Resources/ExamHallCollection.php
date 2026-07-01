<?php

namespace App\Modules\Examination\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ExamHallCollection extends ResourceCollection
{
    public $collects = ExamHallResource::class;
}
