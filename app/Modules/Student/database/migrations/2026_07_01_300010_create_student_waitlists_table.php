<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_waitlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            // Cross-module refs — no DB-level FK; enforced at application layer
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('applicant_name');
            $table->string('guardian_name');
            $table->string('guardian_phone', 20);
            $table->string('guardian_email')->nullable();
            $table->unsignedSmallInteger('position');
            $table->enum('status', ['waiting', 'admitted', 'cancelled'])->default('waiting');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'class_id', 'section_id', 'status'], 'sw_school_class_section_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_waitlists');
    }
};
