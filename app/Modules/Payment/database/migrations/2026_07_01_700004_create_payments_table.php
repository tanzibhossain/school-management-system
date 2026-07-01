<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('receipt_number', 30);
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->unsignedBigInteger('student_id');           // cross-module — denormalized for fast queries
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'bkash', 'sslcommerz', 'bank_transfer', 'cheque', 'waiver']);
            $table->string('transaction_ref', 100)->nullable(); // gateway / bank transfer ref
            $table->enum('gateway_status', ['pending', 'success', 'failed'])->nullable();

            // Cheque-specific fields
            $table->string('cheque_number', 30)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->date('cheque_date')->nullable();            // post-dated cheques common in BD
            $table->enum('cheque_status', ['submitted', 'cleared', 'bounced'])->nullable();

            $table->boolean('is_reversed')->default(false);    // true when cheque bounced
            $table->unsignedBigInteger('collected_by');        // cross-module — no FK
            $table->timestamp('paid_at');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'receipt_number'], 'pay_school_receipt_unique');
            $table->index(['school_id', 'student_id'], 'pay_school_student_idx');
            $table->index(['school_id', 'method'], 'pay_school_method_idx');
            $table->index(['school_id', 'is_reversed'], 'pay_school_reversed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
