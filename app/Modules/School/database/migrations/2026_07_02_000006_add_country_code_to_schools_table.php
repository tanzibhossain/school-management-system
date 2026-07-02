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
            // ISO 3166-1 alpha-2 — gates which payment gateways a school can use
            // (BD: bKash + SSLCommerz; others: Stripe + PayPal). No neutral default: set at onboarding.
            $table->char('country_code', 2)->nullable()->after('address');
        });

        // Backfill: all existing tenants are Bangladesh schools
        DB::table('schools')->update(['country_code' => 'BD']);
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });
    }
};
