<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('invoice_number', 30);
            $table->unsignedBigInteger('student_id');           // cross-module — no FK
            $table->unsignedBigInteger('academic_year_id');     // cross-module — no FK
            $table->unsignedTinyInteger('month')->nullable();   // 1–12 for monthly; null for one-time/yearly
            $table->decimal('amount_due', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('credit_applied', 10, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'waived', 'cancelled'])->default('unpaid');
            $table->date('due_date');
            $table->string('note')->nullable();                 // reason for waiver / cancellation
            $table->unsignedBigInteger('issued_by');            // cross-module — no FK
            $table->timestamps();

            $table->unique(['school_id', 'invoice_number'], 'inv_school_number_unique');
            $table->index(['school_id', 'student_id', 'status'], 'inv_school_student_status_idx');
            $table->index(['school_id', 'academic_year_id', 'month'], 'inv_school_year_month_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
