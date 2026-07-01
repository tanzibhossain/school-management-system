<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('eiin_code')->nullable();
            $table->string('school_code')->nullable();
            $table->string('technical_branch_code')->nullable();
            $table->date('established')->nullable();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->string('sms_api_key')->nullable();
            $table->string('sms_sender_id')->nullable();
            $table->boolean('auto_due_enabled')->default(false);
            $table->decimal('fine_per_day', 8, 2)->default(0.00);
            $table->string('quick_payment_process')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
