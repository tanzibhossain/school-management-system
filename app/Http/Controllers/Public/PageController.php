<?php

namespace App\Http\Controllers\Public;

use App\Modules\School\Models\School;
use App\Modules\Website\Models\SiteSetting;
use App\Modules\Website\Services\PageRenderService;
use App\Modules\Website\Services\PublicPortalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

/**
 * Renders a published Website page by slug at "/{slug}", driving the block
 * layout through PageRenderService. Honours the module's slug-redirect chain.
 */
class PageController extends Controller
{
    public function __construct(
        private readonly PublicPortalService $portal,
        private readonly PageRenderService $render,
    ) {}

    public function show(string $slug): View|RedirectResponse
    {
        $school = School::current();
        abort_unless($school, 404);

        $page = $this->portal->pageBySlug($school->id, $slug);

        if (! $page) {
            $newSlug = $this->portal->resolveRedirect($school->id, $slug);
            if ($newSlug) {
                return redirect()->route('page.show', $newSlug);
            }
            abort(404);
        }

        $layout = $page->publishedLayout->first();

        return view('public.page', [
            'page' => $page,
            'view' => $this->render->buildView($school->id, $layout?->layout_json),
            'settings' => SiteSetting::forSchool($school->id),
            'school' => $school,
        ]);
    }
}
