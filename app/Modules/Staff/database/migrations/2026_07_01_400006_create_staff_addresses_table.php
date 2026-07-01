<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->enum('type', ['present', 'permanent']);
            $table->string('address')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('thana', 100)->nullable();
            $table->string('post_code', 20)->nullable();
            $table->string('country', 100)->default('Bangladesh');
            $table->timestamps();

            $table->unique(['staff_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_addresses');
    }
};
