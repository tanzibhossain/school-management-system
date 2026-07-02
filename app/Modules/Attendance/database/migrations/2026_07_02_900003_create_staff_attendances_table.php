<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date'); // school-local date — resolved in the school's timezone
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->string('source', 10)->default('manual'); // manual | rfid
            $table->boolean('is_auto_closed')->default(false); // check_out written by auto-close job, not a real punch
            $table->boolean('is_incomplete')->default(false);  // e.g. clock-out with no clock-in — flagged, never invented
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'staff_id', 'date'], 'staff_attendance_unique_day');
            $table->index(['school_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};
