<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 10); // present | absent | late | half_day | leave
            $table->string('note')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('edited_by')->nullable()->constrained('users'); // audit trail for corrections
            $table->timestamps();

            // One status per student per day — bulk register is an upsert against this key
            $table->unique(['school_id', 'student_id', 'date'], 'student_attendance_unique_day');
            $table->index(['school_id', 'class_id', 'date']);
            $table->index(['school_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
