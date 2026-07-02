<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->date('date');
            $table->string('name');
            // government | religious | school | closure
            // 'closure' doubles as the retroactive "void day": adding one for an already-marked
            // date removes that date from every attendance % calculation.
            $table->string('type', 20)->default('school');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['school_id', 'date']);
            $table->index(['school_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
