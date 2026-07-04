<?php

namespace App\Modules\School\Models;

use App\Modules\Platform\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'subdomain',
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
        'lms_ai_api_key',
        'auto_due_enabled',
        'fine_per_day',
        'quick_payment_process',
        'is_active',
        // Platform module (#23) — plan/subscription fields
        'plan_id',
        'trial_ends_at',
        'subscription_expires_at',
        'is_demo',
        'provisioning_type',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_status',
    ];

    protected $hidden = ['sms_api_key', 'lms_ai_api_key', 'stripe_customer_id', 'stripe_subscription_id'];

    protected $casts = [
        'established' => 'date',
        'auto_due_enabled' => 'boolean',
        'fine_per_day' => 'decimal:2',
        'sms_cost_per_segment' => 'decimal:4',
        // DevPlan: "Stored encrypted" — same treatment as Payment's gateway
        // credentials on PaymentConfig.
        'lms_ai_api_key' => 'encrypted',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    // Mirror DB-level defaults (avoid null in responses before refresh)
    protected $attributes = [
        'institution_code_label' => 'Institution Code',
        'currency' => 'USD',
        'timezone' => 'UTC',
        'locale' => 'en',
        'academic_year_pattern' => 'jan_dec',
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

    /** @return BelongsTo<Plan, School> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
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
