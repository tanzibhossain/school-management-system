<?php

namespace App\Modules\Website\Http\Resources;

use App\Modules\Website\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SiteSetting */
class SiteSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'accent_color' => $this->accent_color,
            'background_color' => $this->background_color,
            'surface_color' => $this->surface_color,
            'text_color' => $this->text_color,
            'heading_color' => $this->heading_color,
            'link_color' => $this->link_color,
            'link_hover_color' => $this->link_hover_color,
            'border_color' => $this->border_color,
            'font_heading' => $this->font_heading,
            'font_body' => $this->font_body,
            'base_font_size' => $this->base_font_size,
            'container_width' => $this->container_width,
            'btn_radius' => $this->btn_radius,
            'btn_font_weight' => $this->btn_font_weight,
            'btn_transition_ms' => $this->btn_transition_ms,
            'btn_filled_json' => $this->btn_filled_json,
            'btn_outline_json' => $this->btn_outline_json,
            'global_bg_type' => $this->global_bg_type,
            'global_bg_color' => $this->global_bg_color,
            'global_bg_image' => $this->global_bg_image,
            'global_bg_overlay' => $this->global_bg_overlay,
            'site_name' => $this->site_name,
            'favicon' => $this->favicon,
            'homepage_page_id' => $this->homepage_page_id,
            'maintenance_mode' => $this->maintenance_mode,
            'cookie_banner_text' => $this->cookie_banner_text,
            'ga4_id' => $this->ga4_id,
            'fb_pixel_id' => $this->fb_pixel_id,
            'custom_css' => $this->custom_css,
        ];
    }
}
