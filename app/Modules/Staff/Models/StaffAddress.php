<?php

namespace App\Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAddress extends Model
{
    protected $fillable = [
        'staff_id',
        'type',
        'address',
        'district',
        'thana',
        'post_code',
        'country',
    ];

    /** @return BelongsTo<Staff, StaffAddress> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
