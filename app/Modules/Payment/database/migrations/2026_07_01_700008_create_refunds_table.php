<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->foreignId('payment_id')->constrained('payments');
            $table->decimal('amount', 10, 2);
            $table->decimal('processing_fee', 10, 2)->default(0);
            $table->decimal('net_refund', 10, 2);
            $table->enum('method', ['bkash', 'sslcommerz', 'cash', 'bank_transfer']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('gateway_ref', 100)->nullable();
            $table->unsignedBigInteger('requested_by');         // cross-module — no FK
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status'], 'ref_school_status_idx');
            $table->index('payment_id', 'ref_payment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
