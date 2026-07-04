<?php

namespace App\Modules\Platform\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent by SubscriptionReminderService at 7 days and 1 day before
 * schools.subscription_expires_at. Covers BOTH Super-Admin-created offline/manual
 * accounts (where this is the only renewal nudge that exists) and self-serve Stripe
 * subscriptions nearing their current period end (Stripe itself handles the actual
 * charge retry — this is just an informational heads-up to the school).
 */
class SubscriptionExpiringMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $adminName,
        public readonly string $schoolName,
        public readonly int $daysRemaining,
        public readonly string $expiresOn,
    ) {}

    public function envelope(): Envelope
    {
        $days = $this->daysRemaining === 1 ? '1 day' : "{$this->daysRemaining} days";

        return new Envelope(
            subject: "{$this->schoolName}'s subscription expires in {$days}",
        );
    }

    public function content(): Content
    {
        $name = e($this->adminName);
        $school = e($this->schoolName);
        $days = $this->daysRemaining === 1 ? '1 day' : "{$this->daysRemaining} days";
        $date = e($this->expiresOn);

        return new Content(
            htmlString: <<<HTML
                <p>Hi {$name},</p>
                <p><strong>{$school}</strong>'s subscription expires in {$days}, on {$date}.</p>
                <p>Please renew to avoid any interruption in access.</p>
                HTML
        );
    }
}
