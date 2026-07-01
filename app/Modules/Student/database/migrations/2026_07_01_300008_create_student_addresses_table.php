<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('type', ['present', 'permanent']);
            $table->text('address')->nullable();
            $table->string('district', 100)->nullable();
            $table->string('thana', 100)->nullable();
            $table->string('post_code', 20)->nullable();
            $table->string('country', 100)->default('Bangladesh');
            $table->timestamps();

            $table->unique(['student_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_addresses');
    }
};
