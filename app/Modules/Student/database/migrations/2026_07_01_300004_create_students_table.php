<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('admission_number', 30)->comment('Assigned on form submission');
            $table->string('student_id', 50)->nullable()->comment('Generated on enrolment');
            $table->string('name');
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('nationality', 50)->default('Bangladeshi');
            $table->string('mother_tongue', 50)->nullable();
            $table->string('photo')->nullable()->comment('MinIO object path');
            $table->enum('status', ['active', 'inactive', 'transferred', 'graduated', 'expelled', 'waitlisted'])
                ->default('active');
            $table->unsignedTinyInteger('re_admission_count')->default(0);
            $table->boolean('is_trash')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'admission_number']);
            $table->unique(['school_id', 'student_id']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
