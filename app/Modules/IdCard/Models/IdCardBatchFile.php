<?php

namespace App\Modules\IdCard\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdCardBatchFile extends Model
{
    protected $fillable = [
        'school_id',
        'batch_id',
        'file_index',
        'file_path',
        'card_count',
    ];

    protected $casts = [
        'file_index' => 'integer',
        'card_count' => 'integer',
    ];

    /** @return BelongsTo<IdCardBatch, IdCardBatchFile> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(IdCardBatch::class, 'batch_id');
    }

    /** @param Builder<IdCardBatchFile> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
