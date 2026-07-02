<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-student subject enrollment — prerequisite for the Mark module.
     * is_optional marks the student's optional (4th) subject; drives the
     * bd_national GPA bonus, N/A on tabulation, and teacher mark-entry scoping.
     */
    public function up(): void
    {
        Schema::create('student_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_relation_id')->constrained('subject_relations')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->boolean('is_optional')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'subject_relation_id', 'academic_year_id'], 'student_subject_unique');
            $table->index(['school_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subjects');
    }
};
