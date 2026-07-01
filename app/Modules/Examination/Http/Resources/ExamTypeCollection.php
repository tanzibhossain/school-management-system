<?php

namespace App\Modules\Examination\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ExamTypeCollection extends ResourceCollection
{
    public $collects = ExamTypeResource::class;
}
