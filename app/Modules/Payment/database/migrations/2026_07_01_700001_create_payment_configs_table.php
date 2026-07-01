<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_configs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id')->unique();

            // Invoice & receipt numbering
            $table->string('invoice_prefix', 10)->default('INV');
            $table->unsignedInteger('invoice_last_seq')->default(0);
            $table->string('receipt_prefix', 10)->default('REC');
            $table->unsignedInteger('receipt_last_seq')->default(0);

            // Fee configuration
            $table->decimal('bkash_fee_pct', 5, 2)->default(1.50);
            $table->decimal('sslcommerz_fee_pct', 5, 2)->default(2.00);
            $table->decimal('bounce_fee_amount', 10, 2)->default(0.00);

            // bKash credentials (encrypted at application layer)
            $table->text('bkash_app_key')->nullable();
            $table->text('bkash_app_secret')->nullable();
            $table->text('bkash_username')->nullable();
            $table->text('bkash_password')->nullable();
            $table->string('bkash_base_url', 255)->nullable();   // sandbox vs production

            // SSLCommerz credentials (encrypted at application layer)
            $table->text('sslcommerz_store_id')->nullable();
            $table->text('sslcommerz_store_pass')->nullable();
            $table->string('sslcommerz_base_url', 255)->nullable();

            $table->timestamps();

            $table->index('school_id', 'pay_cfg_school_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_configs');
    }
};
