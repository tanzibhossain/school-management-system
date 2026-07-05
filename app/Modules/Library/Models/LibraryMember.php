<?php

namespace App\Modules\Library\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryMember extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'member_type',
        'membership_number',
        'joined_at',
        'is_active',
        'is_trash',
    ];

    protected $casts = [
        'joined_at' => 'date',
        'is_active' => 'boolean',
        'is_trash' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function borrowRecords(): HasMany
    {
        return $this->hasMany(BorrowRecord::class);
    }

    /** @param Builder<LibraryMember> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<LibraryMember> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_trash', false);
    }
}
