<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Global-neutral defaults — BD values are applied per school (seed template), never hardcoded
            $table->char('currency', 3)->default('USD')->after('email');
            $table->string('timezone', 64)->default('UTC')->after('currency');
            $table->string('locale', 10)->default('en')->after('timezone');
            $table->string('academic_year_pattern', 20)->default('jan_dec')->after('locale');
        });

        // Backfill: every existing tenant row predates globalisation and is a Bangladesh school
        DB::table('schools')->update([
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
        ]);
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['currency', 'timezone', 'locale', 'academic_year_pattern']);
        });
    }
};
