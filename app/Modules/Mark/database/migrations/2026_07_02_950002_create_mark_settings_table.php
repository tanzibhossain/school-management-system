<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('mode', 10)->default('mark');                    // mark | grade
            $table->string('result_strategy', 30)->default('bd_national');  // bd_national | simple_average | weighted_average | percentage_only
            $table->boolean('show_merit_position')->default(true);          // BD template default: visible
            $table->decimal('grace_marks_cap', 5, 2)->default(5.00);        // max grace per subject
            $table->timestamps();

            $table->unique(['school_id', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_settings');
    }
};
