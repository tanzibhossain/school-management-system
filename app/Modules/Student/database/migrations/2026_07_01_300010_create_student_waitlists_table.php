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
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->foreignId('class_id')->constrained('classes');
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('applicant_name');
            $table->string('guardian_name');
            $table->string('guardian_phone', 20);
            $table->string('guardian_email')->nullable();
            $table->unsignedSmallInteger('position');
            $table->enum('status', ['waiting', 'admitted', 'cancelled'])->default('waiting');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'class_id', 'section_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_waitlists');
    }
};
