<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\Website\Models\SiteSetting;
use App\Modules\Website\Services\SiteSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Public-site appearance: site name, brand colours, and the top header bar
 * (welcome text, phone, text colour). Edits the Website module's SiteSetting.
 */
class AppearanceController extends Controller
{
    public function __construct(private readonly SiteSettingService $settings) {}

    public function edit(): View
    {
        return view('admin.setup.appearance', [
            'settings' => SiteSetting::forSchool(app('current_school_id')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name'         => ['nullable', 'string', 'max:150'],
            'primary_color'     => ['nullable', 'string', 'max:20'],
            'accent_color'      => ['nullable', 'string', 'max:20'],
            'heading_color'     => ['nullable', 'string', 'max:20'],
            'topbar_welcome'    => ['nullable', 'string', 'max:255'],
            'topbar_phone'      => ['nullable', 'string', 'max:100'],
            'topbar_text_color' => ['nullable', 'string', 'max:20'],
        ]);

        $this->settings->update(app('current_school_id'), $data);

        return back()->with('status', 'Appearance updated.');
    }
}
