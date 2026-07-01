<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_payment_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->boolean('online_payment_enabled')->default(false);
            $table->boolean('offline_payment_enabled')->default(true);
            $table->enum('gateway', ['bkash', 'sslcommerz'])->nullable();
            $table->boolean('sandbox_mode')->default(true);

            // bKash credentials (stored encrypted)
            $table->string('bkash_merchant_number')->nullable();
            $table->text('bkash_app_key')->nullable();
            $table->text('bkash_app_secret')->nullable();
            $table->text('bkash_username')->nullable();
            $table->text('bkash_password')->nullable();

            // SSLCommerz credentials (stored encrypted)
            $table->string('sslcommerz_store_id')->nullable();
            $table->text('sslcommerz_store_pass')->nullable();

            $table->timestamps();
            $table->unique('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_payment_settings');
    }
};
