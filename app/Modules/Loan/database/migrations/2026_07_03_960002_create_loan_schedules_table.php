<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_loan_id')->constrained('staff_loans')->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            // Repayment tracking is deferred until the Payroll module (#21) exists to drive salary
            // deductions — these columns are reserved for that integration, not written to yet.
            $table->boolean('is_paid')->default(false);
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['staff_loan_id', 'installment_number']);
            $table->index(['school_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};
