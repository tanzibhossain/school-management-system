<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_routines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('period_id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('routine_rooms')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('routine_periods')->cascadeOnDelete();
            $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
            // Prevent double-booking a room for the same period+day
            $table->unique(['school_id', 'room_id', 'period_id', 'day_of_week'], 'unique_room_slot');
            // Prevent a section from having two subjects at the same time
            $table->unique(['school_id', 'section_id', 'period_id', 'day_of_week'], 'unique_section_slot');
            $table->index(['school_id', 'class_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_routines');
    }
};
