<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds gateway_payment_id to the payments table.
 *
 * Stores the gateway's own payment reference that is needed for refunds
 * and differs from transaction_ref:
 *  - bKash:       paymentID (from createPayment)  → gateway_payment_id
 *                 trxID    (from executePayment) → transaction_ref
 *  - SSLCommerz:  bank_tran_id (from validation)  → gateway_payment_id
 *                 val_id                          → transaction_ref
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('gateway_payment_id', 100)
                ->nullable()
                ->after('transaction_ref')
                ->comment('bKash paymentID / SSLCommerz bank_tran_id — required for refunds');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('gateway_payment_id');
        });
    }
};
