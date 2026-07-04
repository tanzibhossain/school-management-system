<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('lms_assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('file_path');
            $table->dateTime('submitted_at');
            $table->boolean('late_submission')->default(false);
            $table->unsignedInteger('marks_awarded')->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();

            // One submission per student per assignment — no resubmission flow
            // was scoped for this pass (not in the DevPlan's spec).
            $table->unique(['assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_submissions');
    }
};
