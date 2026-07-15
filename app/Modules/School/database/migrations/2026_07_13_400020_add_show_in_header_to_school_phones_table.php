<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_phones', function (Blueprint $table): void {
            // Phones flagged here are shown (clickable, tel:) in the public header's
            // top-right column. Replaces the old free-text "label" in the UI.
            $table->boolean('show_in_header')->default(false)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('school_phones', function (Blueprint $table): void {
            $table->dropColumn('show_in_header');
        });
    }
};
