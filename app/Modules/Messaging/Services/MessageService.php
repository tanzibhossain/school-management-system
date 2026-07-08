<?php

namespace App\Modules\Messaging\Services;

use App\Models\User;
use App\Modules\Messaging\Events\MessageSent;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageAttachment;
use App\Modules\Messaging\Models\MessageParticipant;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Repositories\MessageRepository;
use App\Modules\Messaging\Repositories\ThreadRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class MessageService
{
    public function __construct(
        private readonly MessageRepository $messages,
        private readonly ThreadRepository $threads,
    ) {}

    /**
     * Post a message to a thread. Sender must be an active participant and the
     * thread must be unlocked. Not cached — the thread's caches are flushed.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function send(int $schoolId, MessageThread $thread, User $sender, string $body, array $files = []): Message
    {
        if ($thread->is_locked) {
            throw new UnprocessableEntityHttpException('This conversation is locked.');
        }

        $participant = MessageParticipant::where('thread_id', $thread->id)
            ->where('user_id', $sender->id)->whereNull('left_at')->first();

        if (! $participant) {
            throw new AccessDeniedHttpException('You are not a participant in this conversation.');
        }

        $message = DB::transaction(function () use ($schoolId, $thread, $sender, $body, $files, $participant): Message {
            $message = Message::create([
                'school_id' => $schoolId,
                'thread_id' => $thread->id,
                'sender_id' => $sender->id,
                'body' => $body,
            ]);

            foreach ($files as $file) {
                $path = $file->store("messaging/{$schoolId}/{$thread->id}", 'minio');
                MessageAttachment::create([
                    'school_id' => $schoolId,
                    'message_id' => $message->id,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                ]);
            }

            $thread->update(['last_message_at' => now()]);
            // Sender has implicitly read their own message.
            $participant->update(['last_read_message_id' => $message->id, 'last_read_at' => now()]);

            return $message;
        });

        event(new MessageSent($message));
        $this->messages->flush();
        $this->threads->flush();

        return $message->fresh(['attachments']);
    }

    public function markRead(int $schoolId, MessageThread $thread, int $userId): MessageParticipant
    {
        $participant = MessageParticipant::where('thread_id', $thread->id)
            ->where('user_id', $userId)->whereNull('left_at')->firstOrFail();

        $latestId = Message::where('thread_id', $thread->id)->max('id');

        $participant->update([
            'last_read_message_id' => $latestId ?? $participant->last_read_message_id,
            'last_read_at' => now(),
        ]);
        $this->threads->flush();

        return $participant->fresh();
    }

    /** @return Collection<int, Message> */
    public function forThread(int $schoolId, int $threadId, ?int $afterId = null)
    {
        return $this->messages->forThread($schoolId, $threadId, $afterId);
    }

    /** Global unread badge — messages newer than each thread's read mark, not the user's own. */
    public function unreadCountFor(int $schoolId, int $userId): int
    {
        $rows = MessageParticipant::forSchool($schoolId)
            ->where('user_id', $userId)->whereNull('left_at')->get(['thread_id', 'last_read_message_id']);

        $total = 0;
        foreach ($rows as $row) {
            $total += Message::where('thread_id', $row->thread_id)
                ->where('id', '>', $row->last_read_message_id ?? 0)
                ->where('sender_id', '!=', $userId)
                ->count();
        }

        return $total;
    }

    public function deleteOwn(int $schoolId, int $messageId, int $userId): void
    {
        $message = Message::forSchool($schoolId)->findOrFail($messageId);

        if ($message->sender_id !== $userId) {
            throw new AccessDeniedHttpException('You can only delete your own messages.');
        }

        $message->delete();
        $this->messages->flush();
    }

    /** Guard read access: the user must be an active participant. */
    public function assertParticipant(int $schoolId, MessageThread $thread, int $userId): void
    {
        $ok = MessageParticipant::where('thread_id', $thread->id)
            ->where('user_id', $userId)->whereNull('left_at')->exists();

        if (! $ok) {
            throw new AccessDeniedHttpException('You are not a participant in this conversation.');
        }
    }
}
