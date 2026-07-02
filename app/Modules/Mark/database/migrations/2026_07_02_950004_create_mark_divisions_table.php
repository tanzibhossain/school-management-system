<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_divisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('exam_subject_id')->constrained('exam_subjects')->cascadeOnDelete();
            $table->string('name', 50);                      // Attendance / Assignment / Mid / Final …
            $table->decimal('max_marks', 6, 2);
            $table->decimal('pass_mark', 6, 2)->nullable();  // optional per-division pass requirement
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['exam_subject_id', 'name']);
            $table->index(['school_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_divisions');
    }
};
