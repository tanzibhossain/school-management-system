<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            // Top header bar content + its text colour (background uses primary_color).
            $table->string('topbar_welcome')->nullable()->after('site_name');
            $table->string('topbar_phone')->nullable()->after('topbar_welcome');
            $table->string('topbar_text_color', 20)->default('#ffffff')->after('topbar_phone');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['topbar_welcome', 'topbar_phone', 'topbar_text_color']);
        });
    }
};
