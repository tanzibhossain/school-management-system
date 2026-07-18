<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generalise the gateway/method columns from fixed enums to strings so any
 * gateway slug from config/payment_gateways.php fits with no further migration.
 * The set of allowed values is governed at the application layer (the registry
 * plus request validation), not by the database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('method', 30)->change();
        });

        Schema::table('refunds', function (Blueprint $table): void {
            $table->string('method', 30)->change();
        });

        Schema::table('payment_gateway_logs', function (Blueprint $table): void {
            $table->string('gateway', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->enum('method', ['cash', 'bkash', 'sslcommerz', 'bank_transfer', 'cheque', 'waiver'])->change();
        });

        Schema::table('refunds', function (Blueprint $table): void {
            $table->enum('method', ['bkash', 'sslcommerz', 'cash', 'bank_transfer'])->change();
        });

        Schema::table('payment_gateway_logs', function (Blueprint $table): void {
            $table->enum('gateway', ['bkash', 'sslcommerz'])->change();
        });
    }
};
