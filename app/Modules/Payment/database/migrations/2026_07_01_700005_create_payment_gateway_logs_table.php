<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('payment_id')->nullable(); // nullable — logged before Payment row exists
            $table->enum('gateway', ['bkash', 'sslcommerz']);
            $table->string('action', 50);                        // grant_token|create|execute|query|verify|refund
            $table->json('payload');                             // what we sent
            $table->json('response');                            // what we received
            $table->string('status', 30);
            $table->timestamps();

            $table->index(['school_id', 'gateway'], 'pglog_school_gateway_idx');
            $table->index('payment_id', 'pglog_payment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_logs');
    }
};
