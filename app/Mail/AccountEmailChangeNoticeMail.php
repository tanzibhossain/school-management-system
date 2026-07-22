<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Sent to the CURRENT (old) address when an email change is requested —
 * the "wasn't you?" half of AccountEmailChangeMail. Deliberately carries its
 * own cancel link that does NOT require being logged in: if the account is
 * actually compromised, the real owner may already be locked out, so the
 * one channel they still control (their old inbox) needs a way to stop the
 * change without a password. ShouldQueue: see AccountEmailChangeMail's note
 * on why nothing in here may throw.
 */
class AccountEmailChangeNoticeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $newEmail,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your account email is being changed'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-email-change-notice',
            with: [
                'userName' => $this->user->name,
                'newEmail' => $this->newEmail,
                'cancelUrl' => URL::temporarySignedRoute(
                    'account.email.cancel-external',
                    now()->addHours(24),
                    ['user' => $this->user->id, 'token' => $this->token],
                ),
            ],
        );
    }
}
