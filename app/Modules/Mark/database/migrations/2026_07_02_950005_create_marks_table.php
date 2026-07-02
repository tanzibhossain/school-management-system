<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('mark_division_id')->constrained('mark_divisions')->cascadeOnDelete();
            $table->decimal('marks_obtained', 6, 2)->nullable(); // null when absent
            $table->boolean('is_absent')->default(false);        // absent ≠ zero — displayed as "Ab"
            $table->decimal('grace_marks', 5, 2)->default(0);    // audited, never mixed into marks_obtained
            $table->foreignId('grace_given_by')->nullable()->constrained('users');
            $table->foreignId('entered_by')->constrained('users');
            $table->timestamp('locked_at')->nullable();          // set when the exam result is locked
            $table->timestamps();

            $table->unique(['student_id', 'mark_division_id'], 'mark_unique_per_division');
            $table->index(['school_id', 'exam_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marks');
    }
};
