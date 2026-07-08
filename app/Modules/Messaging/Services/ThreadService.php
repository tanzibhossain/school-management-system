<?php

namespace App\Modules\Messaging\Services;

use App\Models\User;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageParticipant;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Repositories\ThreadRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ThreadService
{
    public function __construct(
        private readonly ThreadRepository $threads,
        private readonly MessagingPolicyService $policy,
        private readonly MessageService $messages,
    ) {}

    /**
     * Start (or, for a 1:1, reuse) a thread. participantIds excludes the creator,
     * who is always added. A body (with optional files) posts the first message.
     *
     * @param  array<int>  $participantIds
     * @param  array<int, UploadedFile>  $files
     */
    public function create(int $schoolId, User $creator, array $participantIds, ?string $subject, ?string $body, array $files = []): MessageThread
    {
        $ids = collect($participantIds)->push($creator->id)->unique()->values();
        $users = User::forSchool($schoolId)->findMany($ids);

        if ($users->count() !== $ids->count()) {
            throw new UnprocessableEntityHttpException('One or more participants are invalid for this school.');
        }

        $this->policy->assertValidParticipants($creator, $users, $schoolId);

        $isDirect = $users->count() === 2;
        $directKey = $isDirect ? $this->directKey($users->pluck('id')->all()) : null;

        // 1:1 dedupe — reuse an existing direct thread for the same pair.
        if ($isDirect) {
            $existing = MessageThread::forSchool($schoolId)->where('direct_key', $directKey)->first();
            if ($existing) {
                if ($body !== null && $body !== '') {
                    $this->messages->send($schoolId, $existing, $creator, $body, $files);
                }

                return $existing->fresh(['participants']);
            }
        }

        $thread = DB::transaction(function () use ($schoolId, $creator, $users, $subject, $isDirect, $directKey, $body, $files): MessageThread {
            $thread = MessageThread::create([
                'school_id' => $schoolId,
                'type' => $isDirect ? 'direct' : 'group',
                'subject' => $isDirect ? null : $subject,
                'direct_key' => $directKey,
                'created_by' => $creator->id,
                'last_message_at' => null,
            ]);

            foreach ($users as $user) {
                MessageParticipant::create([
                    'school_id' => $schoolId,
                    'thread_id' => $thread->id,
                    'user_id' => $user->id,
                ]);
            }

            if ($body !== null && $body !== '') {
                $this->messages->send($schoolId, $thread, $creator, $body, $files);
            }

            return $thread;
        });

        $this->threads->flush();

        return $thread->fresh(['participants']);
    }

    public function addParticipant(int $schoolId, int $threadId, User $adder, int $newUserId): MessageThread
    {
        $thread = MessageThread::forSchool($schoolId)->findOrFail($threadId);

        if ($thread->type === 'direct') {
            throw new UnprocessableEntityHttpException('Cannot add participants to a direct conversation — start a group instead.');
        }

        $isAdderParticipant = MessageParticipant::where('thread_id', $thread->id)
            ->where('user_id', $adder->id)->whereNull('left_at')->exists();
        if (! $isAdderParticipant) {
            throw new UnprocessableEntityHttpException('Only a participant can add others.');
        }

        $newUser = User::forSchool($schoolId)->findOrFail($newUserId);
        $this->policy->assertCanAddParticipant($adder, $newUser, $schoolId);

        MessageParticipant::updateOrCreate(
            ['thread_id' => $thread->id, 'user_id' => $newUser->id],
            ['school_id' => $schoolId, 'left_at' => null],
        );
        $this->threads->flush();

        return $thread->fresh(['participants']);
    }

    public function removeParticipant(int $schoolId, int $threadId, int $userId): MessageThread
    {
        $thread = MessageThread::forSchool($schoolId)->findOrFail($threadId);

        $remainingIds = MessageParticipant::where('thread_id', $thread->id)
            ->whereNull('left_at')->where('user_id', '!=', $userId)->pluck('user_id');

        $remainingHaveStaff = User::whereIn('id', $remainingIds)->get()
            ->contains(fn (User $u) => $this->policy->isStaff($u));

        if (! $remainingHaveStaff) {
            throw new UnprocessableEntityHttpException('A conversation must keep at least one staff member.');
        }

        MessageParticipant::where('thread_id', $thread->id)->where('user_id', $userId)
            ->update(['left_at' => now()]);
        $this->threads->flush();

        return $thread->fresh(['participants']);
    }

    /** Inbox with a live per-thread unread count for the given user. */
    public function inbox(int $schoolId, int $userId): EloquentCollection
    {
        $threads = $this->threads->inboxFor($schoolId, $userId);

        foreach ($threads as $thread) {
            $participant = $thread->participants->firstWhere('user_id', $userId);
            $lastRead = $participant?->last_read_message_id ?? 0;
            $thread->setAttribute('unread_count', $this->unreadForThread($thread->id, $lastRead, $userId));
        }

        return $threads;
    }

    private function unreadForThread(int $threadId, int $lastReadId, int $userId): int
    {
        return Message::where('thread_id', $threadId)
            ->where('id', '>', $lastReadId)
            ->where('sender_id', '!=', $userId)
            ->count();
    }

    /** @param array<int> $ids */
    private function directKey(array $ids): string
    {
        sort($ids);

        return implode(':', $ids);
    }
}
