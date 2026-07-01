<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_discounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name', 100);
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 8, 2);                         // 10.00 = 10% or BDT 10
            $table->decimal('max_amount', 10, 2)->nullable();       // cap for percentage discounts
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'name'], 'fee_disc_school_name_unique');
            $table->index('school_id', 'fee_disc_school_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_discounts');
    }
};
