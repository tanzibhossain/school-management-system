<?php

namespace App\Modules\Platform\Services;

use App\Models\User;
use App\Modules\Platform\Mail\SubscriptionExpiringMail;
use App\Modules\Platform\Models\SubscriptionReminder;
use App\Modules\School\Models\School;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * Daily job — covers BOTH Super-Admin-created offline/manual accounts (this is
 * their only renewal nudge) and self-serve Stripe subscriptions nearing their
 * current period end (Stripe itself handles the actual charge retry; this is just
 * an informational heads-up). Idempotent via the subscription_reminders unique
 * (school_id, milestone) constraint — running the job twice in a day never
 * double-emails.
 */
class SubscriptionReminderService
{
    public function checkAndSend(): int
    {
        $sent = 0;

        foreach (Config::get('platform.reminder_days', [7, 1]) as $days) {
            $milestone = $days === 1 ? 'day_1' : 'day_7';

            $schools = School::query()
                ->whereNotNull('subscription_expires_at')
                ->where('is_demo', false)
                ->whereDate('subscription_expires_at', now()->addDays($days)->toDateString())
                ->get();

            foreach ($schools as $school) {
                if (SubscriptionReminder::where('school_id', $school->id)->where('milestone', $milestone)->exists()) {
                    continue; // already sent for this milestone
                }

                $admin = User::where('school_id', $school->id)->role('admin')->first();

                if (! $admin) {
                    continue;
                }

                Mail::to($admin->email)->send(new SubscriptionExpiringMail(
                    $admin->name,
                    $school->name,
                    $days,
                    $school->subscription_expires_at->toFormattedDateString(),
                ));

                SubscriptionReminder::create([
                    'school_id' => $school->id,
                    'milestone' => $milestone,
                    'sent_at' => now(),
                ]);

                $sent++;
            }
        }

        return $sent;
    }
}
