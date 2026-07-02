<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    protected $fillable = [
        'school_id', 'staff_id', 'date', 'check_in', 'check_out',
        'source', 'is_auto_closed', 'is_incomplete', 'note',
    ];

    protected $casts = [
        'date'           => 'date',
        'check_in'       => 'datetime',
        'check_out'      => 'datetime',
        'is_auto_closed' => 'boolean',
        'is_incomplete'  => 'boolean',
    ];

    // Mirror DB-level defaults
    protected $attributes = [
        'source'         => 'manual',
        'is_auto_closed' => false,
        'is_incomplete'  => false,
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /** Open = clocked in but never clocked out. */
    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeOpen($query): void
    {
        $query->whereNotNull('check_in')->whereNull('check_out');
    }
}
