<?php

namespace App\Modules\Staff\Events;

use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Models\StaffAcademic;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffAssignedToClass
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Staff $staff,
        public readonly StaffAcademic $academic,
    ) {}
}
