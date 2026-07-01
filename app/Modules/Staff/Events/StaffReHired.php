<?php

namespace App\Modules\Staff\Events;

use App\Modules\Staff\Models\Staff;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffReHired
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Staff $staff) {}
}
