<?php

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'institution_code',
        'institution_code_label',
        'school_code',
        'technical_branch_code',
        'established',
        'address',
        'country_code',
        'email',
        'currency',
        'timezone',
        'locale',
        'academic_year_pattern',
        'logo',
        'sms_api_key',
        'sms_sender_id',
        'sms_cost_per_segment',
        'auto_due_enabled',
        'fine_per_day',
        'quick_payment_process',
        'is_active',
    ];

    protected $hidden = ['sms_api_key'];

    protected $casts = [
        'established'      => 'date',
        'auto_due_enabled' => 'boolean',
        'fine_per_day'     => 'decimal:2',
        'sms_cost_per_segment' => 'decimal:4',
        'is_active'        => 'boolean',
    ];

    // Mirror DB-level defaults (avoid null in responses before refresh)
    protected $attributes = [
        'institution_code_label' => 'Institution Code',
        'currency'               => 'USD',
        'timezone'               => 'UTC',
        'locale'                 => 'en',
        'academic_year_pattern'  => 'jan_dec',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function phones(): HasMany
    {
        return $this->hasMany(SchoolPhone::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(SchoolOpeningHour::class)->orderBy('day_of_week');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Return the single school record for this tenant container.
     */
    public static function current(): ?static
    {
        return static::first();
    }
}
