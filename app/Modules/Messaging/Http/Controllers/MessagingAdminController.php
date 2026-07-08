<?php

namespace App\Modules\Messaging\Http\Controllers;

use App\Modules\Messaging\Http\Requests\LockThreadRequest;
use App\Modules\Messaging\Http\Resources\MessageResource;
use App\Modules\Messaging\Http\Resources\MessageThreadResource;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Services\MessageService;
use App\Modules\Messaging\Services\MessagingModerationService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class MessagingAdminController extends Controller
{
    public function __construct(
        private readonly MessagingModerationService $moderation,
        private readonly MessageService $messages,
    ) {}

    public function threads(): AnonymousResourceCollection
    {
        return MessageThreadResource::collection($this->moderation->allThreads(app('current_school_id')));
    }

    public function messages(int $threadId): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        MessageThread::forSchool($schoolId)->findOrFail($threadId); // ensure in-school

        return MessageResource::collection($this->messages->forThread($schoolId, $threadId));
    }

    public function lock(LockThreadRequest $request, int $threadId): MessageThreadResource
    {
        return new MessageThreadResource(
            $this->moderation->setLocked(app('current_school_id'), $threadId, $request->validated()['locked'])
        );
    }
}
