<?php

namespace App\Modules\Website\Models;

use App\Support\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasTranslations;

    public const GLOBAL_BG_TYPES = ['color', 'image'];

    /**
     * Curated Google Fonts allow-list for font_heading/font_body — deliberately
     * a fixed set (not free text) validated server-side (SchoolController)
     * AND re-checked at render time (public/layout.blade.php) before ever
     * being interpolated into a <style> block or a Google Fonts URL. A free-
     * text font name would be a CSS/HTML injection vector the moment it's
     * wired into a real render path (these columns previously went nowhere,
     * so this risk never mattered before). 'System default' isn't listed
     * here — it's simply what a null value means, same convention as every
     * other optional SiteSetting color/appearance field.
     */
    public const FONTS = [
        'Inter', 'Poppins', 'Nunito', 'Roboto', 'Open Sans',
        'Merriweather', 'Lora', 'Playfair Display',
    ];

    /** btn_font_weight allow-list — matches Google Fonts' standard weight axis stops. */
    public const BTN_FONT_WEIGHTS = ['400', '500', '600', '700'];

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
    public static function forSchool(int $schoolId): self
    {
        return static::firstOrCreate(['school_id' => $schoolId]);
    }
}
