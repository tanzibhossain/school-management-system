<?php

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'eiin_code',
        'school_code',
        'technical_branch_code',
        'established',
        'address',
        'email',
        'logo',
        'sms_api_key',
        'sms_sender_id',
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
        'is_active'        => 'boolean',
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
