<?php

namespace App\Modules\Payroll\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One school's monthly payroll cycle. Lifecycle: draft (created, no entries yet)
 * -> draft + processed_at set (entries generated, re-processable/idempotent) ->
 * approved (loan installments finalized, numbers locked). No endpoint transitions
 * a run to 'paid' in this pass — the DevPlan's own route list never defines one;
 * see CLAUDE.md's Payroll section for that documented gap.
 */
class PayrollRun extends Model
{
    public const STATUSES = ['draft', 'approved', 'paid'];

    protected $fillable = [
        'school_id',
        'month',
        'year',
        'status',
        'notes',
        'processed_by',
        'processed_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return HasMany<PayrollEntry> */
    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    /** @return BelongsTo<User, PayrollRun> */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /** @return BelongsTo<User, PayrollRun> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @param Builder<PayrollRun> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
