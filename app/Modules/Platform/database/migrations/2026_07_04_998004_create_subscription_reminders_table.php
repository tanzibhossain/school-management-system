<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unlike plans/pending_school_signups, this one genuinely IS school-scoped —
        // it tracks a specific school's renewal reminders. It just happens to be
        // written to by a platform-level scheduled job (queries across ALL schools),
        // not through the normal current_school_id request-scoping every other module
        // uses.
        Schema::create('subscription_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('milestone', ['day_7', 'day_1'])->comment(
                'Which reminder this is, relative to schools.subscription_expires_at'
            );
            $table->timestamp('sent_at');
            $table->timestamps();

            // One reminder per milestone per school — the daily job checks this before
            // sending so it never double-emails on a retry/overlap.
            $table->unique(['school_id', 'milestone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_reminders');
    }
};
