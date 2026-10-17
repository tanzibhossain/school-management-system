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
        abort_unless($school !== null, 404);

        $page = $this->portal->pageBySlug($school->id, $slug);

        if (! $page) {
            $newSlug = $this->portal->resolveRedirect($school->id, $slug);
            if ($newSlug) {
                return redirect()->route('page.show', $newSlug);
            }
            abort(404);
        }

        // renderPage() is cached (keyed by the published layout's own id);
        // null only when the page has NEITHER this locale's NOR the default
        // locale's published layout, in which case this still renders —
        // just with empty blocks, same as before. app()->getLocale() is set
        // by SetLocale from the session's chosen language (Language module).
        $view = $this->render->renderPage($page, app()->getLocale()) ?? $this->render->buildView($school->id, null, app()->getLocale());

        return view('public.page', [
            'page' => $page,
            'view' => $view,
            'settings' => SiteSetting::forSchool($school->id),
            'school' => $school,
        ]);
    }
}
