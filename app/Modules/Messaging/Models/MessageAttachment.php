<?php

namespace App\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    protected $fillable = [
        'school_id',
        'message_id',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    /** @return BelongsTo<Message, MessageAttachment> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }
}
