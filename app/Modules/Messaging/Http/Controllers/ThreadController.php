<?php

namespace App\Modules\Messaging\Http\Controllers;

use App\Modules\Messaging\Http\Requests\AddParticipantRequest;
use App\Modules\Messaging\Http\Requests\StoreThreadRequest;
use App\Modules\Messaging\Http\Resources\MessageThreadResource;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Services\MessageService;
use App\Modules\Messaging\Services\MessagingPolicyService;
use App\Modules\Messaging\Services\ThreadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ThreadController extends Controller
{
    public function __construct(
        private readonly ThreadService $threads,
        private readonly MessageService $messages,
        private readonly MessagingPolicyService $policy,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return MessageThreadResource::collection(
            $this->threads->inbox(app('current_school_id'), $request->user()->id)
        );
    }

    public function store(StoreThreadRequest $request): JsonResponse
    {
        $thread = $this->threads->create(
            app('current_school_id'),
            $request->user(),
            $request->validated()['participant_ids'],
            $request->validated()['subject'] ?? null,
            $request->validated()['body'] ?? null,
            $request->file('attachments', []),
        );

        return (new MessageThreadResource($thread))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id): MessageThreadResource
    {
        $thread = MessageThread::forSchool(app('current_school_id'))->findOrFail($id);
        $this->messages->assertParticipant(app('current_school_id'), $thread, $request->user()->id);

        return new MessageThreadResource($thread->load('participants'));
    }

    public function addParticipant(AddParticipantRequest $request, int $id): MessageThreadResource
    {
        $thread = $this->threads->addParticipant(
            app('current_school_id'), $id, $request->user(), $request->validated()['user_id']
        );

        return new MessageThreadResource($thread);
    }

    public function removeParticipant(Request $request, int $id, int $userId): MessageThreadResource
    {
        // Staff may remove anyone; anyone may remove themselves (leave).
        if ($userId !== $request->user()->id && ! $this->policy->isStaff($request->user())) {
            throw new AccessDeniedHttpException('Only staff can remove other participants.');
        }

        return new MessageThreadResource(
            $this->threads->removeParticipant(app('current_school_id'), $id, $userId)
        );
    }
}
