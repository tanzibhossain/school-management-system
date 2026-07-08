<?php

namespace App\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageThread extends Model
{
    protected $fillable = [
        'school_id',
        'type',
        'subject',
        'direct_key',
        'created_by',
        'last_message_at',
        'is_locked',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    /** @return HasMany<MessageParticipant> */
    public function participants(): HasMany
    {
        return $this->hasMany(MessageParticipant::class, 'thread_id');
    }

    /** @return HasMany<Message> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    /** @param Builder<MessageThread> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
