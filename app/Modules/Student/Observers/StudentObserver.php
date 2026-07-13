<?php

namespace App\Modules\Student\Observers;

use App\Modules\Student\Models\Student;
use App\Support\CacheTags;

class StudentObserver
{
    public function saved(Student $student): void
    {
        CacheTags::flush(['students', 'waitlist']);
    }

    public function deleted(Student $student): void
    {
        CacheTags::flush(['students', 'waitlist']);
    }
}
