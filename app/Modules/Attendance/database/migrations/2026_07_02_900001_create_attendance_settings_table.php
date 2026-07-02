<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            // closing_time = auto clock-out at that day's school closing time (default)
            // max_shift    = auto clock-out at check_in + max_shift_hours
            // off          = never auto-close
            $table->string('auto_close_policy', 20)->default('closing_time');
            $table->unsignedTinyInteger('max_shift_hours')->default(12);
            $table->unsignedTinyInteger('edit_window_days')->default(7);
            $table->unsignedSmallInteger('late_threshold_minutes')->default(15);
            $table->boolean('leave_counts_in_denominator')->default(true);
            $table->timestamps();

            $table->unique('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
