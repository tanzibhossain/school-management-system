<?php

namespace App\Modules\Website\Http\Controllers;

use App\Modules\Website\Http\Requests\UploadWebsiteMediaRequest;
use App\Modules\Website\Http\Resources\WebsiteMediaResource;
use App\Modules\Website\Models\WebsiteMedia;
use App\Modules\Website\Repositories\WebsiteMediaRepository;
use App\Modules\Website\Services\WebsiteMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class WebsiteMediaController extends Controller
{
    public function __construct(
        private readonly WebsiteMediaService $service,
        private readonly WebsiteMediaRepository $repository,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return WebsiteMediaResource::collection($this->repository->forSchool(app('current_school_id')));
    }

    public function store(UploadWebsiteMediaRequest $request): JsonResponse
    {
        $media = $this->service->upload(app('current_school_id'), $request->file('file'), $request->user());

        if ($request->validated('alt_text')) {
            $media->update(['alt_text' => $request->validated('alt_text')]);
        }

        return (new WebsiteMediaResource($media))->response()->setStatusCode(201);
    }

    public function destroy(int $id): JsonResponse
    {
        $media = WebsiteMedia::forSchool(app('current_school_id'))->findOrFail($id);
        $this->service->delete($media);

        return response()->json(null, 204);
    }
}
