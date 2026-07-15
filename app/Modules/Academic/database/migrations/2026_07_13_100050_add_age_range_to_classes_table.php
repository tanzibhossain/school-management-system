<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table): void {
            // Optional admission age gate per class (null = no restriction).
            $table->unsignedTinyInteger('min_age')->nullable()->after('name');
            $table->unsignedTinyInteger('max_age')->nullable()->after('min_age');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table): void {
            $table->dropColumn(['min_age', 'max_age']);
        });
    }
};
