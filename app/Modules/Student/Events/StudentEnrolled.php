<?php

namespace App\Modules\Student\Events;

use App\Modules\Student\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentEnrolled
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Student $student) {}
}
