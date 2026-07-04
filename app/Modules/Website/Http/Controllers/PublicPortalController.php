<?php

namespace App\Modules\Website\Http\Controllers;

use App\Modules\Website\Http\Resources\Public\PublicNoticeResource;
use App\Modules\Website\Http\Resources\Public\PublicRoutineResource;
use App\Modules\Website\Http\Resources\Public\PublicStaffResource;
use App\Modules\Website\Http\Resources\Public\SiteChromeResource;
use App\Modules\Website\Http\Resources\PageResource;
use App\Modules\Website\Services\PublicPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/**
 * Every method here is public/unauthenticated — served for the (not-yet-built)
 * Next.js public site. Nothing writes; all reads are scoped to the resolved
 * current_school_id, same as any other request (ResolveSchool runs globally
 * on the api middleware group regardless of auth — see bootstrap/app.php).
 */
class PublicPortalController extends Controller
{
    public function __construct(private readonly PublicPortalService $service) {}

    /** GET /public/pages/{slug}. */
    public function page(string $slug): PageResource|JsonResponse
    {
        $page = $this->service->pageBySlug(app('current_school_id'), $slug);

        if (! $page) {
            return response()->json(['message' => 'Page not found.'], 404);
        }

        return new PageResource($page);
    }

    /** GET /public/site-chrome — header + footer + site_settings. */
    public function siteChrome(): SiteChromeResource
    {
        return new SiteChromeResource($this->service->siteChrome(app('current_school_id')));
    }

    /** GET /public/redirect/{slug}. */
    public function redirect(string $slug): JsonResponse
    {
        $destination = $this->service->resolveRedirect(app('current_school_id'), $slug);

        if (! $destination) {
            return response()->json(['message' => 'No redirect found.'], 404);
        }

        return response()->json(['destination_slug' => $destination]);
    }

    /** GET /public/notices — Notice Board dynamic block. */
    public function notices(): AnonymousResourceCollection
    {
        return PublicNoticeResource::collection($this->service->notices(app('current_school_id')));
    }

    /** GET /public/staff — Staff/Teacher List dynamic block. */
    public function staff(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'designation_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
        ]);

        return PublicStaffResource::collection($this->service->staffList(app('current_school_id'), $filters));
    }

    /** GET /public/routine/{classId} — Class Routine dynamic block. */
    public function routine(Request $request, int $classId): AnonymousResourceCollection
    {
        $data = $request->validate(['section_id' => ['required', 'integer']]);

        return PublicRoutineResource::collection(
            $this->service->classRoutine(app('current_school_id'), $classId, $data['section_id'])
        );
    }

    /** GET /public/stats — Stats Counter dynamic block. */
    public function stats(): JsonResponse
    {
        return response()->json(['data' => $this->service->stats(app('current_school_id'))]);
    }

    /** POST /public/results/check — Result Checker dynamic block. */
    public function checkResult(Request $request): JsonResponse
    {
        $data = $request->validate([
            'exam_id' => ['required', 'integer'],
            'roll_number' => ['required', 'string'],
        ]);

        $result = $this->service->checkResult(app('current_school_id'), $data['exam_id'], $data['roll_number']);

        if (! $result) {
            return response()->json(['message' => 'No result found.'], 404);
        }

        return response()->json(['data' => $result]);
    }
}
