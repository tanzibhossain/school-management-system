<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');           // cross-module — no FK
            $table->enum('type', ['credit', 'debit', 'refund']);
            $table->decimal('amount', 10, 2);
            $table->string('reference_type', 30)->nullable();   // 'invoice' | 'payment' | 'refund'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by');           // cross-module — no FK
            $table->timestamps();

            $table->index(['school_id', 'student_id'], 'ct_school_student_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
