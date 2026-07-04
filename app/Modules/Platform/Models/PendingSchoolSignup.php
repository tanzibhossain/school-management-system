<?php

namespace App\Modules\Platform\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform-level — no school_id (the school doesn't exist yet). Staging row for the
 * PAID self-serve signup path only: survives the round-trip out to Stripe Checkout
 * and back via webhook. Trial signups never create one of these — they provision
 * immediately since there's no payment to wait for.
 */
class PendingSchoolSignup extends Model
{
    protected $table = 'pending_school_signups';

    protected $fillable = [
        'school_name',
        'desired_subdomain',
        'plan_id',
        'admin_name',
        'admin_email',
        'country_code',
        'stripe_checkout_session_id',
        'status',
        'created_school_id',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /** @return BelongsTo<Plan, PendingSchoolSignup> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return BelongsTo<School, PendingSchoolSignup> */
    public function createdSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'created_school_id');
    }
}
