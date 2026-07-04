<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // `plan_id` is nullable ON PURPOSE: every school created BEFORE this module
        // existed (all the dev/test data from modules 1-22) stays legacy/grandfathered
        // — unrestricted, never capped by PlanLimitService. Every NEW school created
        // through Platform's provisioning flow always gets a real plan_id.
        Schema::table('schools', function (Blueprint $table): void {
            $table->foreignId('plan_id')->nullable()->after('is_active')
                ->constrained('plans')->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable()->after('plan_id');
            // Doubles as: (a) the Stripe current-period-end for self-serve Basic/Pro,
            // and (b) the hard expiry date a Super Admin sets for an offline/manual
            // account. Null = doesn't expire (legacy schools, or a plan with no
            // expiry concept).
            $table->timestamp('subscription_expires_at')->nullable()->after('trial_ends_at');
            $table->boolean('is_demo')->default(false)->after('subscription_expires_at');
            $table->enum('provisioning_type', ['self_service', 'offline_manual', 'super_admin'])
                ->nullable()->after('is_demo');
            $table->string('stripe_customer_id')->nullable()->after('provisioning_type');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->enum('subscription_status', ['trialing', 'active', 'past_due', 'canceled', 'expired'])
                ->nullable()->after('stripe_subscription_id');

            $table->index(['is_demo']);
            $table->index(['subscription_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn([
                'trial_ends_at',
                'subscription_expires_at',
                'is_demo',
                'provisioning_type',
                'stripe_customer_id',
                'stripe_subscription_id',
                'subscription_status',
            ]);
        });
    }
};
