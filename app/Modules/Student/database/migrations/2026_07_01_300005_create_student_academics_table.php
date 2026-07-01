<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_academics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            // Cross-module refs — no DB-level FK; enforced at application layer
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('version_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->string('roll_number', 20)->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamp('promoted_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'is_current'], 'sa_school_student_current_idx');
            $table->index(['school_id', 'class_id', 'section_id', 'academic_year_id'], 'sa_school_class_section_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_academics');
    }
};
