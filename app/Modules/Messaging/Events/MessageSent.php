<?php

namespace App\Modules\Messaging\Events;

use App\Modules\Messaging\Models\Message;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired on every message send. The clean seam a later listener uses for the
 * deferred SMS / in-app unread notification (out of v1) — and the natural hook
 * for realtime broadcasting if websockets are added later.
 */
class MessageSent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Message $message) {}
}
