<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_credits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');           // cross-module — no FK
            $table->decimal('balance', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'student_id'], 'sc_school_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_credits');
    }
};
