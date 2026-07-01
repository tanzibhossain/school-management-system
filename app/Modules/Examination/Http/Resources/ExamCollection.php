<?php

namespace App\Modules\Examination\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ExamCollection extends ResourceCollection
{
    public $collects = ExamResource::class;
}
