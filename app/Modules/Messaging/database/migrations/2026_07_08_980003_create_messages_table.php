<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('thread_id')->constrained('message_threads')->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['thread_id', 'id'], 'msg_thread_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
