<?php

namespace App\Modules\Platform\Console;

use App\Modules\Platform\Services\SubscriptionReminderService;
use Illuminate\Console\Command;

/**
 * Daily job — emails schools nearing subscription_expires_at at the 7-day and
 * 1-day marks. Idempotent (subscription_reminders unique constraint), covers both
 * Super-Admin-created offline accounts and self-serve Stripe subscriptions.
 */
class SendSubscriptionReminders extends Command
{
    protected $signature = 'platform:subscription-reminders';

    protected $description = 'Send 7-day/1-day subscription expiry reminder emails';

    public function handle(SubscriptionReminderService $service): int
    {
        $sent = $service->checkAndSend();

        $this->info("Sent {$sent} subscription reminder(s).");

        return self::SUCCESS;
    }
}
