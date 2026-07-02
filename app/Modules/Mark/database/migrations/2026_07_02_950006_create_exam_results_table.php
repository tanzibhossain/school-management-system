<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('total_possible', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('grade', 10)->nullable();
            $table->decimal('gpa', 4, 2)->nullable();
            $table->boolean('is_pass')->default(false);
            $table->unsignedSmallInteger('merit_position')->nullable();
            $table->json('subject_breakdown')->nullable();  // per-subject snapshot for marksheets
            $table->boolean('is_locked')->default(false);   // Moderator approval — never recomputed once locked
            $table->foreignId('locked_by')->nullable()->constrained('users');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->index(['school_id', 'exam_id', 'merit_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
