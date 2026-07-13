<?php

namespace App\Modules\User\Observers;

use App\Models\User;
use App\Support\CacheTags;

class UserObserver
{
    public function saved(User $user): void
    {
        CacheTags::flush(['users']);
    }

    public function deleted(User $user): void
    {
        CacheTags::flush(['users']);
    }
}
