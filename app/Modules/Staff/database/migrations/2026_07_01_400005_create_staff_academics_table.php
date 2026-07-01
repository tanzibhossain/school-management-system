<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_academics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            // Cross-module refs — no DB-level FK; enforced at application layer
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('subject', 100)->nullable()->comment('Refactor to subject_id when Subject model exists');
            $table->boolean('is_class_teacher')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'staff_id', 'academic_year_id'], 'sta_school_staff_year_idx');
            $table->index(['school_id', 'class_id', 'section_id', 'academic_year_id'], 'sta_school_class_section_year_idx');
            // One-class-teacher-per-section enforcement done at application layer (StaffService)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_academics');
    }
};
