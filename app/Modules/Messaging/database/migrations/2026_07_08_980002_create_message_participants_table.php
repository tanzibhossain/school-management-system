<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('thread_id')->constrained('message_threads')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            // Read position — powers unread counts (messages newer than this, not mine).
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['thread_id', 'user_id'], 'mp_thread_user_unique');
            $table->index(['school_id', 'user_id'], 'mp_school_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_participants');
    }
};
