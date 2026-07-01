<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            // Cross-module ref to users — no DB-level FK; enforced at application layer
            $table->unsignedBigInteger('created_by')->comment('User who created the announcement');
            $table->string('title');
            $table->text('body');
            $table->enum('type', ['general', 'event', 'holiday', 'exam', 'fee', 'other'])->default('general');
            $table->enum('audience', ['all', 'teachers', 'students', 'parents'])->default('all');
            $table->enum('priority', ['normal', 'important', 'urgent'])->default('normal');
            $table->timestamp('publish_at')->nullable()->comment('null=draft; past/now=live; future=scheduled');
            $table->timestamp('expire_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_trash')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'publish_at', 'expire_at'], 'ann_school_publish_expire_idx');
            $table->index(['school_id', 'audience', 'is_pinned'], 'ann_school_audience_pinned_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
