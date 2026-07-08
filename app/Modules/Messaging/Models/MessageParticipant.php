<?php

namespace App\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageParticipant extends Model
{
    protected $fillable = [
        'school_id',
        'thread_id',
        'user_id',
        'last_read_message_id',
        'last_read_at',
        'left_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'left_at' => 'datetime',
        'last_read_message_id' => 'integer',
    ];

    /** @return BelongsTo<MessageThread, MessageParticipant> */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    /** @param Builder<MessageParticipant> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** Active (not left) participants. @param Builder<MessageParticipant> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('left_at');
    }
}
