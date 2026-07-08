<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_transport_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('transport_route_id')->constrained('transport_routes')->cascadeOnDelete();
            $table->string('pickup_point', 150)->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            // "expired" is derived from ends_on at read time (scopeExpired), never a
            // stored terminal status — only active | ended live here.
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->timestamps();

            $table->index(['school_id', 'transport_route_id', 'status'], 'sta_school_route_status_idx');
            $table->index(['school_id', 'student_id', 'status'], 'sta_school_student_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transport_assignments');
    }
};
