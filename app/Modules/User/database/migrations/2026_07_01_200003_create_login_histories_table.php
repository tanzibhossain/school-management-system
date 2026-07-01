<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();  // nullable — failed attempts may have no user
            $table->string('email');
            $table->string('ip_address', 45)->nullable();
            $table->string('device_name')->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['success', 'failed']);
            $table->string('failed_reason')->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('logged_out_at')->nullable();

            $table->index(['user_id', 'logged_in_at']);
            $table->index(['school_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
