<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_hall_seats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hall_id')->constrained('exam_halls')->cascadeOnDelete();
            $table->unsignedSmallInteger('row');           // 1–n
            $table->enum('side', ['L', 'R']);
            $table->unsignedTinyInteger('position');       // 1–seats_per_row
            $table->string('label', 20);                   // e.g. "R01-L2", "R22-R1"
            $table->boolean('is_available')->default(true); // false = blocked (door, pillar, broken bench)
            $table->timestamps();

            $table->unique(['hall_id', 'row', 'side', 'position']);
            $table->index(['hall_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_hall_seats');
    }
};
