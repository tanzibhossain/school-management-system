<?php

namespace App\Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffExperience extends Model
{
    protected $fillable = [
        'staff_id',
        'institution',
        'designation',
        'from_date',
        'to_date',
        'notes',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    /** @return BelongsTo<Staff, StaffExperience> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
