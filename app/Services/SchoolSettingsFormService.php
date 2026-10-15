<?php

namespace App\Services;

use App\Modules\Language\Models\Language;
use App\Modules\Language\Services\TranslationService;
use App\Modules\School\Models\School;
use App\Modules\School\Services\SchoolService;
use App\Modules\Website\Models\SiteSetting;
use App\Modules\Website\Services\SiteSettingService;

/**
 * Saves the admin "School settings" screen (Admin\Setup\SchoolController@update).
 * That one form spans three modules — the School profile, the Website
 * SiteSetting (branding/theme/SEO) and Language translations — plus the phone
 * list, so the orchestration lives here (shared, like PdfRenderingService)
 * rather than in any one module's service or in the controller.
 */
class SchoolSettingsFormService
{
    /** School/SiteSetting fields translatable per docs/modules/30-multilingual-content-plan.md Phase 4. */
    public const SCHOOL_TRANSLATABLE_FIELDS = [
        'name', 'institution_code_label', 'institution_code',
        'school_code_label', 'school_code',
        'technical_branch_code_label', 'technical_branch_code', 'address',
    ];

    public const SETTING_TRANSLATABLE_FIELDS = ['meta_title', 'meta_description'];

    public function __construct(
        private readonly SchoolService $schools,
        private readonly SiteSettingService $siteSettings,
        private readonly TranslationService $translations,
    ) {}

    /**
     * @param  array<string, mixed>  $validated  the controller's validated input
     * @param  array<string, string>  $images  stored paths keyed by field (logo, favicon, og_image, global_bg_image); only uploaded ones
     * @param  array<int|string, mixed>  $phones  raw phones[] rows from the form
     * @param  int  $primaryPhone  index into $phones of the primary number
     */
    public function save(School $school, array $validated, array $images, array $phones, int $primaryPhone): void
    {
        $this->saveProfile($school, $validated, $images);
        $settings = $this->siteSettings->update($school->id, $this->settingData($validated, $images));
        $this->saveTranslations($school, $settings, $validated);
        $this->schools->syncPhones($school->id, $this->phoneRows($phones, $primaryPhone));
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, string>  $images
     */
    private function saveProfile(School $school, array $validated, array $images): void
    {
        $schoolData = collect($validated)->only([
            'name', 'email',
            'institution_code', 'institution_code_label',
            'school_code', 'school_code_label',
            'technical_branch_code', 'technical_branch_code_label',
            'address', 'country_code', 'currency', 'timezone', 'locale', 'academic_year_pattern',
        ])->all();
        // "established" is entered as a plain year; the column stores a date.
        $schoolData['established'] = filled($validated['established'] ?? null) ? $validated['established'].'-01-01' : null;
        if (isset($images['logo'])) {
            $schoolData['logo'] = $images['logo'];
        }

        $this->schools->updateSettings($school, $schoolData);
    }

    /**
     * Appearance / SEO (SiteSetting).
     *
     * @param  array<string, mixed>  $validated
     * @param  array<string, string>  $images
     * @return array<string, mixed>
     */
    private function settingData(array $validated, array $images): array
    {
        $settingData = collect($validated)->only([
            'primary_color', 'accent_color', 'heading_color',
            'topbar_text_color', 'ticker_position', 'meta_title', 'meta_description',
            'secondary_color', 'background_color', 'surface_color', 'text_color',
            'link_color', 'link_hover_color', 'border_color',
            'font_heading', 'font_body', 'base_font_size', 'container_width',
            'btn_radius', 'btn_font_weight', 'btn_transition_ms',
            'global_bg_type', 'global_bg_color', 'global_bg_overlay',
        ])->all();

        // btn_filled_json/btn_outline_json are stored as a small fixed-key
        // JSON object each ({bg,text} / {border,text}) assembled from their
        // own flat form fields — array_filter() drops empty sub-fields so an
        // admin who only sets one of the two colors doesn't have the other
        // silently stored as an empty string; an entirely-empty result
        // becomes null (falls back to the CSS defaults in layout.blade.php)
        // rather than an empty-but-truthy [] the render side would have to
        // special-case.
        $filled = array_filter([
            'bg' => $validated['btn_filled_bg'] ?? null,
            'text' => $validated['btn_filled_text'] ?? null,
        ]);
        $settingData['btn_filled_json'] = $filled ?: null;
        $outline = array_filter([
            'border' => $validated['btn_outline_border'] ?? null,
            'text' => $validated['btn_outline_text'] ?? null,
        ]);
        $settingData['btn_outline_json'] = $outline ?: null;

        foreach (['favicon', 'og_image', 'global_bg_image'] as $field) {
            if (isset($images[$field])) {
                $settingData[$field] = $images[$field];
            }
        }

        return $settingData;
    }

    /**
     * Only iterates over locales the DB actually knows are active — an
     * arbitrary/stale locale key in the submitted payload (deactivated
     * language, tampered form) is simply ignored rather than stored.
     *
     * @param  array<string, mixed>  $validated
     */
    private function saveTranslations(School $school, SiteSetting $settings, array $validated): void
    {
        $activeLocales = Language::activeCached()
            ->pluck('code')
            ->reject(fn ($code) => $code === Language::defaultCode());

        $submitted = (array) ($validated['translations'] ?? []);
        $schoolTranslations = [];
        $settingTranslations = [];
        foreach ($activeLocales as $locale) {
            $fields = is_array($submitted[$locale] ?? null) ? $submitted[$locale] : [];
            $schoolTranslations[$locale] = array_intersect_key($fields, array_flip(self::SCHOOL_TRANSLATABLE_FIELDS));
            $settingTranslations[$locale] = array_intersect_key($fields, array_flip(self::SETTING_TRANSLATABLE_FIELDS));
        }

        $this->translations->saveMany($school, $schoolTranslations);
        $this->translations->saveMany($settings, $settingTranslations);
    }

    /**
     * Dynamic phone list; each row can be flagged to show in the header.
     *
     * @param  array<int|string, mixed>  $phones
     * @return array<int, array{phone: mixed, is_primary: bool, show_in_header: bool}>
     */
    private function phoneRows(array $phones, int $primaryPhone): array
    {
        return collect($phones)
            ->filter(fn ($p) => filled($p['phone'] ?? null))
            ->map(fn ($p, $i) => [
                'phone' => $p['phone'],
                'is_primary' => $primaryPhone === (int) $i,
                'show_in_header' => (bool) ($p['show_in_header'] ?? false),
            ])->values()->all();
    }
}
