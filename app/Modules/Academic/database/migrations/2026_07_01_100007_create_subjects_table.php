<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name', 150);
            $table->string('sub_code', 30)->nullable();
            $table->boolean('is_trash')->default(false);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
