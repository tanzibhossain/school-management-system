<?php

namespace App\Http\Controllers\Public;

use App\Modules\School\Models\School;
use App\Modules\Website\Models\SiteSetting;
use App\Modules\Website\Services\PageRenderService;
use App\Modules\Website\Services\PublicPortalService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Public school homepage at "/". If a homepage Page with a published block
 * layout exists, it drives the page; otherwise a sensible default landing
 * renders from live data (notices, stats, staff).
 */
class HomeController extends Controller
{
    public function __construct(
        private readonly PublicPortalService $portal,
        private readonly PageRenderService $render,
    ) {}

    public function index(): View
    {
        $school = School::current();

        if (! $school) {
            return view('public.home', [
                'school'   => null,
                'settings' => new SiteSetting,
                'notices'  => new Collection,
                'staff'    => new Collection,
                'stats'    => ['active_students' => 0, 'active_staff' => 0],
            ]);
        }

        // A designated homepage page's published layout wins.
        $home = $this->render->homepage($school->id);
        $layout = $home?->publishedLayout->first();

        if ($home && $layout) {
            return view('public.page', [
                'page'     => $home,
                'view'     => $this->render->buildView($school->id, $layout->layout_json),
                'settings' => SiteSetting::forSchool($school->id),
                'school'   => $school,
            ]);
        }

        // Fallback: default landing built from live data.
        return view('public.home', [
            'school'   => $school,
            'settings' => SiteSetting::forSchool($school->id),
            'notices'  => $this->portal->notices($school->id),
            'staff'    => $this->portal->staffList($school->id),
            'stats'    => $this->portal->stats($school->id),
        ]);
    }
}
