<?php

namespace App\Modules\IdCard\Models;

use App\Models\User;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdCardBatch extends Model
{
    public const TYPES = ['student', 'staff'];

    public const SCOPES = ['single', 'class', 'all'];

    public const STATUSES = ['queued', 'processing', 'completed', 'failed'];

    protected $fillable = [
        'school_id',
        'type',
        'template_id',
        'scope',
        'class_id',
        'section_id',
        'target_ids',
        'total_count',
        'status',
        'error_message',
        'requested_by',
        'generated_at',
    ];

    protected $casts = [
        'target_ids' => 'array',
        'total_count' => 'integer',
        'generated_at' => 'datetime',
    ];

    /** @return BelongsTo<School, IdCardBatch> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<IdCardTemplate, IdCardBatch> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(IdCardTemplate::class, 'template_id');
    }

    /** @return BelongsTo<SchoolClass, IdCardBatch> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<Section, IdCardBatch> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @return BelongsTo<User, IdCardBatch> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return HasMany<IdCardBatchFile> */
    public function files(): HasMany
    {
        return $this->hasMany(IdCardBatchFile::class, 'batch_id')->orderBy('file_index');
    }

    /** @param Builder<IdCardBatch> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<IdCardBatch> $query */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
