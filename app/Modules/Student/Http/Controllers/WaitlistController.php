<?php

namespace App\Modules\Student\Http\Controllers;

use App\Modules\Student\Http\Requests\StoreWaitlistRequest;
use App\Modules\Student\Http\Requests\UpdateWaitlistRequest;
use App\Modules\Student\Http\Resources\WaitlistResource;
use App\Modules\Student\Models\StudentWaitlist;
use App\Modules\Student\Repositories\WaitlistRepository;
use App\Modules\Student\Services\WaitlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class WaitlistController extends Controller
{
    public function __construct(
        private readonly WaitlistService $service,
        private readonly WaitlistRepository $repository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $entries = $this->repository->getWaiting(
            $schoolId,
            $request->integer('class_id'),
            $request->input('section_id') ? (int) $request->section_id : null,
            $request->integer('academic_year_id'),
        );

        return WaitlistResource::collection($entries->load(['schoolClass', 'section']));
    }

    public function store(StoreWaitlistRequest $request): JsonResponse
    {
        $entry = $this->service->addToWaitlist(app('current_school_id'), $request->validated());

        return (new WaitlistResource($entry->load(['schoolClass', 'section'])))->response()->setStatusCode(201);
    }

    public function update(UpdateWaitlistRequest $request, int $id): WaitlistResource
    {
        $entry = StudentWaitlist::where('school_id', app('current_school_id'))->findOrFail($id);
        $entry->update($request->validated());

        return new WaitlistResource($entry->fresh());
    }

    public function cancel(int $id): JsonResponse
    {
        $entry = StudentWaitlist::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->service->cancel($entry);

        return response()->json(['message' => 'Waitlist entry cancelled.']);
    }
}
