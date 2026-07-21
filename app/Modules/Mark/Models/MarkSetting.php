<?php

namespace App\Modules\Mark\Models;

use Illuminate\Database\Eloquent\Model;

class MarkSetting extends Model
{
    public const MODES = ['mark', 'grade'];

    public const STRATEGIES = ['bd_national', 'simple_average', 'weighted_average', 'percentage_only'];

    protected $fillable = [
        'school_id', 'class_id', 'mode', 'result_strategy',
        'show_merit_position', 'grace_marks_cap',
    ];

    protected $casts = [
        'show_merit_position' => 'boolean',
        'grace_marks_cap' => 'decimal:2',
    ];

    // Mirror DB-level defaults
    protected $attributes = [
        'mode' => 'mark',
        'result_strategy' => 'bd_national',
        'show_merit_position' => true,
        'grace_marks_cap' => 5.00,
    ];

    /** Get (or lazily create) settings for a class. */
    public static function forClass(int $schoolId, int $classId): static
    {
        return static::firstOrCreate(['school_id' => $schoolId, 'class_id' => $classId]);
    }
}
