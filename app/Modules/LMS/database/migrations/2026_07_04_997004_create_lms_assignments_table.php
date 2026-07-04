<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            // Datetime, not date — "late" is determined against a time-of-day
            // cutoff, not just a calendar day.
            $table->dateTime('due_date');
            $table->unsignedInteger('max_marks');
            $table->boolean('allow_late_submission')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_assignments');
    }
};
