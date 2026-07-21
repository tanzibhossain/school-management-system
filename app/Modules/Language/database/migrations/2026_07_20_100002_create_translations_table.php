<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('locale', 10);            // languages.code
            $table->text('key');                     // the English source string
            $table->string('key_hash', 40);          // sha1(key) — TEXT can't be uniquely indexed
            $table->text('value')->nullable();       // null = not translated yet
            $table->timestamps();

            $table->unique(['locale', 'key_hash']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
