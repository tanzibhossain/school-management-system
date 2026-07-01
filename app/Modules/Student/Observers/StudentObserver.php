<?php

namespace App\Modules\Student\Observers;

use App\Modules\Student\Models\Student;
use Illuminate\Support\Facades\Cache;

class StudentObserver
{
    public function saved(Student $student): void
    {
        Cache::tags(['students', 'waitlist'])->flush();
    }

    public function deleted(Student $student): void
    {
        Cache::tags(['students', 'waitlist'])->flush();
    }
}
