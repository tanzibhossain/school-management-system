<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            // Top header bar text colour (background uses primary_color).
            $table->string('topbar_text_color', 20)->default('#ffffff')->after('site_name');
            // Announcement ticker placement relative to the nav bar.
            $table->enum('ticker_position', ['above_nav', 'below_nav', 'hidden'])->default('below_nav')->after('topbar_text_color');
            // SEO / social share defaults for the public site.
            $table->string('meta_title')->nullable()->after('ticker_position');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('og_image')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['topbar_text_color', 'ticker_position', 'meta_title', 'meta_description', 'og_image']);
        });
    }
};
