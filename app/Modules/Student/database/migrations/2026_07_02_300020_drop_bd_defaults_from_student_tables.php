<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Global product rule: no BD values hardcoded in core. Nationality and
     * country come from user input (frontend prefills from the school's
     * country_code) — never from a DB-level Bangladeshi default.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('nationality', 50)->nullable()->default(null)->change();
        });

        Schema::table('student_addresses', function (Blueprint $table): void {
            $table->string('country', 100)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('nationality', 50)->default('Bangladeshi')->change();
        });

        Schema::table('student_addresses', function (Blueprint $table): void {
            $table->string('country', 100)->default('Bangladesh')->change();
        });
    }
};
