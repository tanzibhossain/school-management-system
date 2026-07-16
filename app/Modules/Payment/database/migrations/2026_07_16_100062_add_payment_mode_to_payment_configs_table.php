<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_configs', function (Blueprint $table): void {
            // How the school accepts fees: offline only, online only, or both.
            $table->string('payment_mode', 10)->default('offline')->after('school_id'); // offline | online | both
            // Which online gateways are switched on (BD gateways).
            $table->boolean('bkash_enabled')->default(false)->after('payment_mode');
            $table->boolean('sslcommerz_enabled')->default(false)->after('bkash_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('payment_configs', function (Blueprint $table): void {
            $table->dropColumn(['payment_mode', 'bkash_enabled', 'sslcommerz_enabled']);
        });
    }
};
