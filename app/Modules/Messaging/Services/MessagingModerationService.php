<?php

namespace App\Modules\Messaging\Services;

use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Repositories\ThreadRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Admin oversight (role:admin). Read-only visibility across the school plus a
 * lock toggle — admins are NOT auto-participants and never inject messages.
 */
class MessagingModerationService
{
    public function __construct(private readonly ThreadRepository $threads) {}

    /** @return Collection<int, MessageThread> */
    public function allThreads(int $schoolId): Collection
    {
        return MessageThread::forSchool($schoolId)
            ->with(['participants'])
            ->orderByDesc('last_message_at')->orderByDesc('id')->get();
    }

    public function setLocked(int $schoolId, int $threadId, bool $locked): MessageThread
    {
        $thread = MessageThread::forSchool($schoolId)->findOrFail($threadId);
        $thread->update(['is_locked' => $locked]);
        $this->threads->flush();

        return $thread->fresh(['participants']);
    }
}
