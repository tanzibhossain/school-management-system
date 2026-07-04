<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained('schools')->cascadeOnDelete();

            // Colour palette
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('accent_color')->nullable();
            $table->string('background_color')->nullable();
            $table->string('surface_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('heading_color')->nullable();
            $table->string('link_color')->nullable();
            $table->string('link_hover_color')->nullable();
            $table->string('border_color')->nullable();

            // Typography
            $table->string('font_heading')->nullable();
            $table->string('font_body')->nullable();
            $table->unsignedInteger('base_font_size')->nullable();
            $table->unsignedInteger('container_width')->nullable();

            // Buttons — JSON holds all four states (default/hover/active/disabled) per variant.
            $table->unsignedInteger('btn_radius')->nullable();
            $table->string('btn_font_weight')->nullable();
            $table->unsignedInteger('btn_transition_ms')->nullable();
            $table->json('btn_filled_json')->nullable();
            $table->json('btn_outline_json')->nullable();

            // Global background
            $table->enum('global_bg_type', ['color', 'image'])->default('color');
            $table->string('global_bg_color')->nullable();
            $table->string('global_bg_image')->nullable();
            $table->decimal('global_bg_overlay', 3, 2)->nullable();

            // General site settings
            $table->string('site_name')->nullable();
            $table->string('favicon')->nullable();
            $table->foreignId('homepage_page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->boolean('maintenance_mode')->default(false);
            $table->text('cookie_banner_text')->nullable();
            $table->string('ga4_id')->nullable();
            $table->string('fb_pixel_id')->nullable();
            $table->longText('custom_css')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
