<?php

namespace App\Modules\Messaging\Http\Controllers;

use App\Modules\Messaging\Http\Requests\SendMessageRequest;
use App\Modules\Messaging\Http\Resources\MessageResource;
use App\Modules\Messaging\Models\MessageAttachment;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageController extends Controller
{
    public function __construct(private readonly MessageService $messages) {}

    public function index(Request $request, int $threadId): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $thread = MessageThread::forSchool($schoolId)->findOrFail($threadId);
        $this->messages->assertParticipant($schoolId, $thread, $request->user()->id);

        $after = $request->query('after');

        return MessageResource::collection(
            $this->messages->forThread($schoolId, $threadId, $after ? (int) $after : null)
        );
    }

    public function store(SendMessageRequest $request, int $threadId): JsonResponse
    {
        $schoolId = app('current_school_id');
        $thread = MessageThread::forSchool($schoolId)->findOrFail($threadId);

        $message = $this->messages->send(
            $schoolId, $thread, $request->user(),
            $request->validated()['body'] ?? '',
            $request->file('attachments', []),
        );

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    public function read(Request $request, int $threadId): JsonResponse
    {
        $schoolId = app('current_school_id');
        $thread = MessageThread::forSchool($schoolId)->findOrFail($threadId);
        $this->messages->markRead($schoolId, $thread, $request->user()->id);

        return response()->json(['message' => 'Marked as read.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->messages->deleteOwn(app('current_school_id'), $id, $request->user()->id);

        return response()->json(['message' => 'Message deleted.']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->messages->unreadCountFor(app('current_school_id'), $request->user()->id),
        ]);
    }

    public function downloadAttachment(Request $request, int $id): StreamedResponse
    {
        $schoolId = app('current_school_id');
        $attachment = MessageAttachment::where('school_id', $schoolId)->findOrFail($id);
        $thread = MessageThread::forSchool($schoolId)->findOrFail($attachment->message->thread_id);

        $user = $request->user();
        if (! $user->hasRole(['admin', 'super_admin'])) {
            $this->messages->assertParticipant($schoolId, $thread, $user->id);
        }

        return Storage::disk('minio')->download($attachment->file_path, $attachment->original_name);
    }
}
