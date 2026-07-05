<?php

namespace App\Modules\Library\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'school_id',
        'title',
        'author',
        'isbn',
        'category',
        'publisher',
        'edition',
        'published_at',
        'total_copies',
        'available_copies',
        'is_active',
        'is_trash',
    ];

    protected $casts = [
        'published_at' => 'date',
        'total_copies' => 'integer',
        'available_copies' => 'integer',
        'is_active' => 'boolean',
        'is_trash' => 'boolean',
    ];

    public function borrowRecords(): HasMany
    {
        return $this->hasMany(BorrowRecord::class);
    }

    /** @param Builder<Book> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<Book> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_trash', false);
    }

    /** @param Builder<Book> $query */
    public function scopeAvailable(Builder $query): Builder
    {
        return $this->active()->where('available_copies', '>', 0);
    }
}
