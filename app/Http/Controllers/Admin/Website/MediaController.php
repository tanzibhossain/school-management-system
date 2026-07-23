<?php

namespace App\Http\Controllers\Admin\Website;

use App\Modules\Website\Models\WebsiteMedia;
use App\Modules\Website\Repositories\WebsiteMediaRepository;
use App\Modules\Website\Services\WebsiteMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * JSON endpoints backing the page editor's Media Library modal (see
 * resources/views/admin/website/pages/edit.blade.php's #media-picker-modal).
 * Deliberately plain arrays, not a JsonResource — this is editor-internal
 * plumbing, not a versioned API surface. Wires up WebsiteMediaService/
 * WebsiteMediaRepository/WebsiteMedia, which existed with no controller or
 * route pointing at them at all before this (see
 * docs/modules/28-elementor-block-editor-plan.md §7h).
 */
class MediaController extends Controller
{
    public function __construct(
        private readonly WebsiteMediaRepository $media,
        private readonly WebsiteMediaService $service,
    ) {}

    public function index(): JsonResponse
    {
        $schoolId = app('current_school_id');

        return response()->json(
            $this->media->forSchool($schoolId)->map(fn (WebsiteMedia $m) => $this->present($m))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            // Images (what the picker is built for today) plus mp4/webm so
            // it also covers the Video block's self-hosted file/poster
            // fields — anything else, upload it elsewhere and paste the URL
            // into the field directly, same as before this existed.
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm'],
        ]);

        $schoolId = app('current_school_id');
        $media = $this->service->upload($schoolId, $request->file('file'), $request->user());

        return response()->json($this->present($media), 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $schoolId = app('current_school_id');
        $media = WebsiteMedia::forSchool($schoolId)->findOrFail($id);
        $this->service->delete($media);

        return response()->json(['deleted' => true]);
    }

    /** @return array<string, mixed> */
    private function present(WebsiteMedia $media): array
    {
        return [
            'id' => $media->id,
            'filename' => $media->filename,
            'url' => route('website-media.show', $media->id),
            'width' => $media->width_px,
            'height' => $media->height_px,
            'is_image' => str_starts_with($media->mime_type, 'image/'),
        ];
    }
}
