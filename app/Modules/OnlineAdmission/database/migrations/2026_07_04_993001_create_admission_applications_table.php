<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            // Public lookup key for an applicant with no login — always paired with
            // guardian_phone on the status-check endpoint (a sequential reference
            // alone is guessable).
            $table->string('reference_number');
            $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');

            // Applicant profile — same shape as Student's own core fields, so
            // conversion on approval maps cleanly onto StudentService::enrol().
            $table->string('applicant_name');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('dob')->nullable();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();

            // No section at application time — section is a placement decision
            // made at approval, once capacity is actually known.
            $table->foreignId('desired_class_id')->constrained('classes');
            $table->foreignId('desired_academic_year_id')->constrained('academic_years');

            $table->string('guardian_name');
            $table->string('guardian_phone');
            $table->string('guardian_email')->nullable();
            $table->enum('guardian_relation', ['father', 'mother', 'local_guardian', 'other']);
            $table->text('notes')->nullable();

            // Decision
            $table->text('decision_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            // Set once approval creates the real Student — nullOnDelete (not cascade):
            // deleting the resulting Student later shouldn't erase application history.
            $table->foreignId('created_student_id')->nullable()->constrained('students')->nullOnDelete();

            $table->timestamps();

            $table->unique(['school_id', 'reference_number']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
    }
};
