<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\Language\Models\Language;
use App\Modules\Language\Models\Translation;
use App\Modules\Language\Services\TranslationScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Language management (Botble-style): enable languages, pick the default, and
 * edit every UI string per language. English is the source (English-as-key),
 * so only non-English locales carry editable rows.
 */
class LanguageController extends Controller
{
    public function index(): View
    {
        return view('admin.setup.languages.index', [
            'languages' => Language::orderBy('sort_order')->get(),
            'counts' => Translation::selectRaw('locale, count(*) as total, sum(case when value is not null then 1 else 0 end) as done')
                ->groupBy('locale')->get()->keyBy('locale'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:languages,code'],
            'name' => ['required', 'string', 'max:100'],
            'native_name' => ['required', 'string', 'max:100'],
            'flag' => ['nullable', 'string', 'max:10'],
            'is_rtl' => ['nullable', 'boolean'],
        ]);

        Language::create($data + ['is_active' => true, 'sort_order' => Language::max('sort_order') + 1]);
        Language::flushCache();

        return back()->with('status', __('Language Added.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $language = Language::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'native_name' => ['sometimes', 'string', 'max:100'],
            'flag' => ['nullable', 'string', 'max:10'],
            'is_rtl' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // The default language can never be deactivated.
        if ($language->is_default) {
            $data['is_active'] = true;
        }
        $language->update($data);
        Language::flushCache();

        return back()->with('status', __('Language Updated.'));
    }

    public function setDefault(int $id): RedirectResponse
    {
        $language = Language::findOrFail($id);
        Language::query()->update(['is_default' => false]);
        $language->update(['is_default' => true, 'is_active' => true]);
        Language::flushCache();

        return back()->with('status', __('Default Language Changed.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $language = Language::findOrFail($id);
        if ($language->is_default || $language->code === 'en') {
            return back()->with('error', __('The Default And English Languages Cannot Be Removed.'));
        }
        Translation::where('locale', $language->code)->delete();
        Translation::flushCache($language->code);
        $language->delete();
        Language::flushCache();

        return back()->with('status', __('Language Removed.'));
    }

    // ── Translations editor ──────────────────────────────────────────────────

    public function translations(Request $request, string $code): View
    {
        $language = Language::where('code', $code)->where('code', '!=', 'en')->firstOrFail();

        $query = Translation::where('locale', $code)->orderBy('key');
        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('key', 'like', "%{$search}%")->orWhere('value', 'like', "%{$search}%"));
        }
        if ($request->boolean('missing')) {
            $query->whereNull('value');
        }

        return view('admin.setup.languages.translations', [
            'language' => $language,
            'rows' => $query->paginate(50)->withQueryString(),
            'search' => $search,
            'missingOnly' => $request->boolean('missing'),
        ]);
    }

    public function saveTranslations(Request $request, string $code): RedirectResponse
    {
        Language::where('code', $code)->firstOrFail();
        $values = $request->input('t', []); // [translation_id => value]

        foreach ($values as $id => $value) {
            $row = Translation::where('locale', $code)->find((int) $id);
            if ($row) {
                $row->update(['value' => filled($value) ? $value : null]);
            }
        }
        Translation::flushCache($code);

        return back()->with('status', __('Translations Saved.'));
    }

    /** Re-scan the codebase for __() strings and register missing keys. */
    public function scan(TranslationScanner $scanner): RedirectResponse
    {
        $added = $scanner->sync();

        return back()->with('status', __('Scan Complete — :count New Strings Registered.', ['count' => $added]));
    }
}
