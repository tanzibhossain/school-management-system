<?php

namespace App\Modules\Examination\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ExamSeatingCollection extends ResourceCollection
{
    public $collects = ExamSeatingResource::class;
}
