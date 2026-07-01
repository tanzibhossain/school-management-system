<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_guardians', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Set when guardian has a parent-portal login');
            $table->enum('relation', ['father', 'mother', 'local_guardian', 'other']);
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('photo')->nullable()->comment('MinIO object path');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_guardians');
    }
};
