<?php

namespace App\Modules\User\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function saved(User $user): void
    {
        Cache::tags(['users'])->flush();
    }

    public function deleted(User $user): void
    {
        Cache::tags(['users'])->flush();
    }
}
