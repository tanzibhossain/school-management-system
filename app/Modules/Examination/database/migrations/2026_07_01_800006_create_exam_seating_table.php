<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_seating', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            // nullOnDelete: if a seat is deleted/regenerated the assignment loses its seat ref
            $table->foreignId('hall_seat_id')->nullable()->constrained('exam_hall_seats')->nullOnDelete();
            $table->string('exam_roll', 20)->nullable();
            // Denormalized from student_academics for fast seating chart renders + admit cards
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->unique(['exam_id', 'hall_seat_id']);
            $table->index(['school_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_seating');
    }
};
