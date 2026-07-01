<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            // Cross-module ref to users — no DB-level FK; enforced at application layer
            $table->unsignedBigInteger('user_id')->nullable()->comment('Portal login account');
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('employee_id', 50)->nullable()->comment('Generated on hire');
            $table->string('name');
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('nationality', 50)->default('Bangladeshi');
            $table->string('mother_tongue', 50)->nullable();
            $table->string('photo')->nullable()->comment('MinIO object path');
            $table->date('joining_date')->nullable();
            $table->date('leaving_date')->nullable();
            $table->enum('employment_type', ['permanent', 'contractual', 'part_time'])->default('permanent');
            $table->decimal('basic_salary', 10, 2)->nullable()->comment('Reference only; full payroll in Payroll module');
            $table->string('rfid_number', 30)->nullable()->comment('Biometric/RFID card number for attendance');
            $table->enum('status', ['active', 'inactive', 'resigned', 'terminated'])->default('active');
            $table->unsignedTinyInteger('re_hire_count')->default(0);
            $table->boolean('is_trash')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'employee_id']);
            $table->index(['school_id', 'status'], 'staff_school_status_idx');
            $table->index(['school_id', 'rfid_number'], 'staff_school_rfid_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
