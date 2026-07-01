<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_halls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            /**
             * Layout config JSON shape:
             * {
             *   "rows": 30,
             *   "sides": [
             *     { "label": "L", "seats_per_row": 4, "blocked_rows": [] },
             *     { "label": "R", "seats_per_row": 2, "blocked_rows": [23,24,25,26] }
             *   ]
             * }
             */
            $table->json('layout_config');
            $table->timestamps();

            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_halls');
    }
};
