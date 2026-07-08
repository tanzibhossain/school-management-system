<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_threads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('type', ['direct', 'group'])->default('direct');
            $table->string('subject', 150)->nullable();
            // Canonical "{minUserId}:{maxUserId}" for direct threads; unique per school
            // makes 1:1 creation idempotent. Null for group threads (many nulls allowed).
            $table->string('direct_key', 40)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'direct_key'], 'mt_school_directkey_unique');
            $table->index(['school_id', 'last_message_at'], 'mt_school_lastmsg_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
