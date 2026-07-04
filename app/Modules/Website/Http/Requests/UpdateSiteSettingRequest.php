<?php

namespace App\Modules\Website\Http\Requests;

use App\Modules\Website\Models\SiteSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'primary_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'secondary_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'accent_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'background_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'surface_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'text_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'heading_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'link_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'link_hover_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'border_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'font_heading' => ['sometimes', 'nullable', 'string', 'max:100'],
            'font_body' => ['sometimes', 'nullable', 'string', 'max:100'],
            'base_font_size' => ['sometimes', 'nullable', 'integer', 'min:8', 'max:32'],
            'container_width' => ['sometimes', 'nullable', 'integer', 'min:320'],
            'btn_radius' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'btn_font_weight' => ['sometimes', 'nullable', 'string', 'max:20'],
            'btn_transition_ms' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'btn_filled_json' => ['sometimes', 'nullable', 'array'],
            'btn_outline_json' => ['sometimes', 'nullable', 'array'],
            'global_bg_type' => ['sometimes', Rule::in(SiteSetting::GLOBAL_BG_TYPES)],
            'global_bg_color' => ['sometimes', 'nullable', 'string', 'max:20'],
            'global_bg_image' => ['sometimes', 'nullable', 'string'],
            'global_bg_overlay' => ['sometimes', 'nullable', 'numeric', 'between:0,1'],
            'site_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'favicon' => ['sometimes', 'nullable', 'string'],
            'homepage_page_id' => ['sometimes', 'nullable', 'integer', 'exists:pages,id'],
            'maintenance_mode' => ['sometimes', 'boolean'],
            'cookie_banner_text' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'ga4_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'fb_pixel_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'custom_css' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
