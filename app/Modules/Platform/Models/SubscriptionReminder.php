<?php

namespace App\Modules\Platform\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Genuinely school-scoped (unlike Plan/PendingSchoolSignup) — but written to by a
 * platform-level scheduled job querying across ALL schools, never through the normal
 * current_school_id request-scoping every other module uses.
 */
class SubscriptionReminder extends Model
{
    protected $table = 'subscription_reminders';

    public $timestamps = true;

    protected $fillable = [
        'school_id',
        'milestone',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /** @return BelongsTo<School, SubscriptionReminder> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
