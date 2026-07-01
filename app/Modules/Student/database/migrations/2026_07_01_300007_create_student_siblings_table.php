<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_siblings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('sibling_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'sibling_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_siblings');
    }
};
