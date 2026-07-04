<?php

namespace App\Modules\Platform\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once, right after a school + admin account are provisioned (any of the
 * three paths: trial self-serve, paid self-serve post-Stripe-webhook, or Super
 * Admin offline creation). No Blade view — same heredoc/plain-HTML-string
 * convention already used for PDFs in PdfRenderingService; a signed, expiring URL
 * is embedded rather than a plaintext password (confirmed decision — never email a
 * real password).
 */
class SetPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $schoolName,
        public readonly string $signedUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your {$this->schoolName} account is ready — set your password",
        );
    }

    public function content(): Content
    {
        $name = e($this->user->name);
        $school = e($this->schoolName);
        $url = e($this->signedUrl);

        return new Content(
            htmlString: <<<HTML
                <p>Hi {$name},</p>
                <p>Your account for <strong>{$school}</strong> has been created.</p>
                <p>Click the link below to set your password and log in. This link expires in 7 days.</p>
                <p><a href="{$url}">Set your password</a></p>
                <p>If you didn't expect this email, you can safely ignore it.</p>
                HTML
        );
    }
}
