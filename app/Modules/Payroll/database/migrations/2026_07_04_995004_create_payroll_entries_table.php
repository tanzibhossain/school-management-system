<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            // Snapshot of every earning/deduction/loan-installment line at the moment this
            // entry was processed — [{label, type: earning|deduction|loan_deduction, amount,
            // loan_schedule_id?}]. Salary components and loan schedules can change later
            // without silently altering an already-processed run's numbers (same snapshot
            // principle Mark uses for marks_obtained).
            $table->json('breakdown')->nullable();
            $table->string('payslip_path')->nullable();
            $table->timestamp('payslip_generated_at')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'staff_id']);
            $table->index(['school_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
    }
};
