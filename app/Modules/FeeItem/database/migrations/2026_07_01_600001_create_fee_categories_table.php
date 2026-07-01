<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'name'], 'fee_cat_school_name_unique');
            $table->index('school_id', 'fee_cat_school_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_categories');
    }
};
