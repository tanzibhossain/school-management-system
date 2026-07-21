<?php

namespace App\Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    public const AUTO_CLOSE_POLICIES = ['closing_time', 'max_shift', 'off'];

    protected $fillable = [
        'school_id', 'auto_close_policy', 'max_shift_hours',
        'edit_window_days', 'late_threshold_minutes', 'leave_counts_in_denominator',
    ];

    protected $casts = [
        'max_shift_hours' => 'integer',
        'edit_window_days' => 'integer',
        'late_threshold_minutes' => 'integer',
        'leave_counts_in_denominator' => 'boolean',
    ];

    // Mirror DB-level defaults
    protected $attributes = [
        'auto_close_policy' => 'closing_time',
        'max_shift_hours' => 12,
        'edit_window_days' => 7,
        'late_threshold_minutes' => 15,
        'leave_counts_in_denominator' => true,
    ];

    /** Get (or lazily create) the settings row for a school. */
    public static function forSchool(int $schoolId): static
    {
        return static::firstOrCreate(['school_id' => $schoolId]);
    }
}
