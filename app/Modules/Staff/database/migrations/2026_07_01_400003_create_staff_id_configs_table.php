<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_id_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('prefix', 20)->default('EMP');
            $table->boolean('include_year')->default(true);
            $table->enum('year_format', ['YYYY', 'YY'])->default('YYYY');
            $table->string('separator', 5)->default('/');
            $table->unsignedTinyInteger('sequence_length')->default(4);
            $table->boolean('reset_yearly')->default(true);
            $table->unsignedInteger('last_sequence')->default(0);
            $table->unsignedSmallInteger('last_reset_year')->nullable();
            $table->timestamps();

            $table->unique('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_id_configs');
    }
};
