<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generic, schema-less gateway credential store. Each gateway's settings live
     * under its slug: { "bkash": {"enabled": true, "mode": "live",
     * "credentials": {...}}, ... }. Adding a new gateway needs NO migration —
     * just a config/payment_gateways.php entry + a driver class. The legacy
     * per-gateway columns are kept as a read fallback for already-stored values.
     */
    public function up(): void
    {
        Schema::table('payment_configs', function (Blueprint $table): void {
            $table->text('gateways')->nullable()->after('payment_mode'); // encrypted JSON (model cast)
        });
    }

    public function down(): void
    {
        Schema::table('payment_configs', function (Blueprint $table): void {
            $table->dropColumn('gateways');
        });
    }
};
