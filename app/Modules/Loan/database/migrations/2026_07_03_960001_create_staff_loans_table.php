<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->decimal('requested_amount', 12, 2); // interest-free — the full amount to be repaid
            $table->unsignedSmallInteger('installment_count');
            $table->string('reason');
            $table->date('start_date'); // due date of the first installment
            $table->string('status', 10)->default('pending'); // pending | approved | rejected | cancelled
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'staff_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_loans');
    }
};
