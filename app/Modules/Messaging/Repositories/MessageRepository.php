<?php

namespace App\Modules\Messaging\Repositories;

use App\Modules\Messaging\Models\Message;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class MessageRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Message::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'message';
    }

    /**
     * Messages in a thread, oldest first; ?afterId returns only newer ones
     * (incremental polling). Live query — no cache on a moving conversation.
     *
     * @return Collection<int, Message>
     */
    public function forThread(int $schoolId, int $threadId, ?int $afterId = null): Collection
    {
        return Message::forSchool($schoolId)
            ->where('thread_id', $threadId)
            ->when($afterId, fn ($q) => $q->where('id', '>', $afterId))
            ->with(['attachments'])
            ->orderBy('id')
            ->get();
    }
}
