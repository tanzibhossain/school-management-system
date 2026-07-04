<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            // subject_id (not subject_relation_id) per the DevPlan's literal LMS
            // schema — no conflict with Academic's subject_relations convention
            // surfaced, so it's followed as specified.
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_courses');
    }
};
