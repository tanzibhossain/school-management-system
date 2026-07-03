<?php

namespace App\Modules\DataImport\Models;

use App\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    public const TYPES = ['student', 'staff'];

    public const STATUSES = ['queued', 'processing', 'completed', 'failed'];

    protected $fillable = [
        'school_id',
        'type',
        'original_filename',
        'stored_path',
        'status',
        'total_rows',
        'success_count',
        'skipped_count',
        'errors',
        'error_message',
        'requested_by',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'total_rows' => 'integer',
        'success_count' => 'integer',
        'skipped_count' => 'integer',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<School, ImportBatch> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<User, ImportBatch> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @param Builder<ImportBatch> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
