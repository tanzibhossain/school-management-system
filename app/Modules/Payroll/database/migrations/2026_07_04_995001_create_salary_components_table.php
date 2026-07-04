<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('component_type', 10); // earning | deduction
            $table->boolean('is_default')->default(false); // seeded default vs school-added
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_trash')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'component_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
