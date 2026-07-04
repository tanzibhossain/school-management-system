<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_templates', function (Blueprint $table): void {
            $table->id();
            // Nullable: null = a global starter template seeded for every school
            // (School Homepage, About Us, Admission, Contact, Notice Board, Result,
            // Blank); non-null = a school's own "Save as Template" custom template.
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('name');
            $table->string('thumbnail')->nullable();
            $table->longText('layout_json');
            $table->timestamps();

            $table->index(['school_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_templates');
    }
};
