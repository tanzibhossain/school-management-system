<?php

namespace App\Modules\Announcement\Http\Controllers;

use App\Modules\Announcement\Http\Requests\ScheduleAnnouncementRequest;
use App\Modules\Announcement\Http\Requests\StoreAnnouncementRequest;
use App\Modules\Announcement\Http\Requests\UpdateAnnouncementRequest;
use App\Modules\Announcement\Http\Resources\AnnouncementListResource;
use App\Modules\Announcement\Http\Resources\AnnouncementResource;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Repositories\AnnouncementRepository;
use App\Modules\Announcement\Services\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementService $service,
        private readonly AnnouncementRepository $repository,
    ) {}

    /** Admin: all non-trashed announcements including drafts and scheduled. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $announcements = $this->repository->paginateForAdmin(
            app('current_school_id'),
            $request->only(['type', 'audience', 'priority', 'include_expired']),
        );

        return AnnouncementListResource::collection($announcements);
    }

    /** Portal users: only published, non-expired, audience-matched announcements. */
    public function feed(Request $request): AnonymousResourceCollection
    {
        $role      = $request->user()->getRoleNames()->first() ?? 'student';
        $audiences = $this->service->audiencesForRole($role);

        $announcements = $this->repository->listVisible(app('current_school_id'), $audiences);

        return AnnouncementResource::collection($announcements);
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $data    = $request->safe()->except('targets');
        $targets = $request->validated()['targets'] ?? [];

        $announcement = $this->service->make(
            app('current_school_id'),
            $request->user(),
            $data,
            $targets,
        );

        return (new AnnouncementResource($announcement))->response()->setStatusCode(201);
    }

    public function show(int $id): AnnouncementResource
    {
        $announcement = Announcement::where('school_id', app('current_school_id'))
            ->with(['targets', 'attachments', 'reads'])
            ->findOrFail($id);

        return new AnnouncementResource($announcement);
    }

    public function update(UpdateAnnouncementRequest $request, int $id): AnnouncementResource
    {
        $announcement = Announcement::where('school_id', app('current_school_id'))->findOrFail($id);
        $announcement->update($request->validated());
        $this->repository->flush();

        return new AnnouncementResource($announcement->fresh(['targets', 'attachments']));
    }

    public function publish(int $id): AnnouncementResource
    {
        $announcement = Announcement::where('school_id', app('current_school_id'))->findOrFail($id);

        return new AnnouncementResource($this->service->publish($announcement));
    }

    public function schedule(ScheduleAnnouncementRequest $request, int $id): AnnouncementResource
    {
        $announcement = Announcement::where('school_id', app('current_school_id'))->findOrFail($id);

        return new AnnouncementResource(
            $this->service->schedule($announcement, $request->validated()['publish_at'])
        );
    }

    public function expire(int $id): AnnouncementResource
    {
        $announcement = Announcement::where('school_id', app('current_school_id'))->findOrFail($id);

        return new AnnouncementResource($this->service->expire($announcement));
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $announcement = Announcement::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->service->markRead($announcement, $request->user()->id);

        return response()->json(['message' => 'Marked as read.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $announcement = Announcement::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->service->trash($announcement);

        return response()->json(['message' => 'Announcement deleted.']);
    }
}
