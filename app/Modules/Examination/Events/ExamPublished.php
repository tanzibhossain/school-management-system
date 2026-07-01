<?php

namespace App\Modules\Examination\Events;

use App\Modules\Examination\Models\Exam;
use Illuminate\Foundation\Events\Dispatchable;

class ExamPublished
{
    use Dispatchable;

    public function __construct(public readonly Exam $exam) {}
}
