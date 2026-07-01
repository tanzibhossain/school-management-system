<?php

namespace App\Modules\Student\Events;

use App\Modules\Student\Models\StudentWaitlist;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentWaitlisted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly StudentWaitlist $entry) {}
}
