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
 * Sent to the NEW address when a user requests an email change — the account
 * update never applies until this link is clicked. ShouldQueue: under the sync
 * queue driver a thrown exception here would surface as a 500 on the request
 * that triggered it, so nothing in handle() may go unhandled (mail failures
 * are logged by Laravel's mail transport, not re-thrown into the request).
 */
class AccountEmailChangeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $newEmail,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Confirm your new email address'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-email-change',
            with: [
                'userName' => $this->user->name,
                'confirmUrl' => URL::temporarySignedRoute(
                    'account.email.confirm',
                    now()->addHours(24),
                    ['user' => $this->user->id, 'token' => $this->token],
                ),
            ],
        );
    }
}
