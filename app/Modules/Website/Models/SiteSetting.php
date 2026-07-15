<?php

namespace App\Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    public const GLOBAL_BG_TYPES = ['color', 'image'];

    protected $fillable = [
        'school_id',
        'primary_color', 'secondary_color', 'accent_color',
        'background_color', 'surface_color', 'text_color', 'heading_color',
        'link_color', 'link_hover_color', 'border_color',
        'font_heading', 'font_body', 'base_font_size', 'container_width',
        'btn_radius', 'btn_font_weight', 'btn_transition_ms',
        'btn_filled_json', 'btn_outline_json',
        'global_bg_type', 'global_bg_color', 'global_bg_image', 'global_bg_overlay',
        'site_name', 'topbar_text_color', 'ticker_position',
        'meta_title', 'meta_description', 'og_image',
        'favicon', 'homepage_page_id', 'maintenance_mode',
        'cookie_banner_text', 'ga4_id', 'fb_pixel_id', 'custom_css',
    ];

    protected $casts = [
        'btn_filled_json' => 'array',
        'btn_outline_json' => 'array',
        'base_font_size' => 'integer',
        'container_width' => 'integer',
        'btn_radius' => 'integer',
        'btn_transition_ms' => 'integer',
        'global_bg_overlay' => 'float',
        'maintenance_mode' => 'boolean',
    ];

    protected $attributes = [
        'global_bg_type' => 'color',
        'maintenance_mode' => false,
        'ticker_position' => 'below_nav',
    ];

    /** Get (or lazily create with defaults) the settings row for a school. */
    public static function forSchool(int $schoolId): static
    {
        return static::firstOrCreate(['school_id' => $schoolId]);
    }
}
