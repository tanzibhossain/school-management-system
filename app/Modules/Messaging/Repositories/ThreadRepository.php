<?php

namespace App\Modules\Messaging\Repositories;

use App\Modules\Messaging\Models\MessageThread;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class ThreadRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(MessageThread::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'message-thread';
    }

    /**
     * A user's inbox — threads they actively participate in, newest activity first.
     * Not cached: inboxes change on every send/read and unread must stay live.
     *
     * @return Collection<int, MessageThread>
     */
    public function inboxFor(int $schoolId, int $userId): Collection
    {
        return MessageThread::forSchool($schoolId)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId)->whereNull('left_at'))
            ->with(['participants'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();
    }
}
