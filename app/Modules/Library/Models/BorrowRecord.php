<?php

namespace App\Modules\Library\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowRecord extends Model
{
    protected $fillable = [
        'school_id',
        'library_member_id',
        'book_id',
        'borrowed_at',
        'due_at',
        'returned_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(LibraryMember::class, 'library_member_id');
    }

    /** @param Builder<BorrowRecord> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Still-out loans (not yet returned).
     *
     * @param Builder<BorrowRecord> $query
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    /**
     * Outstanding loans whose due date has passed — the real "overdue" set.
     * Derived from the data, never stored as a terminal status.
     *
     * @param Builder<BorrowRecord> $query
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('returned_at')->where('due_at', '<', now());
    }
}
