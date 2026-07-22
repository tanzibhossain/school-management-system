<?php

namespace App\Modules\User\Services;

use App\Mail\AccountEmailChangeMail;
use App\Mail\AccountEmailChangeNoticeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

/**
 * Self-service account settings shared by the admin/staff/family portals: name,
 * password, email change (held pending until the new address confirms via a
 * mailed link — never applied instantly), and TOTP two-factor auth.
 */
class AccountService
{
    private const RECOVERY_CODE_COUNT = 8;

    private const PENDING_EMAIL_TTL_HOURS = 24;

    public function updateName(User $user, string $name): void
    {
        $user->update(['name' => $name]);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('Your current password is incorrect.')],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);
    }

    /**
     * Stashes the new address as pending and emails a confirmation link to it
     * — never applies instantly. Also notifies the CURRENT address with a
     * "wasn't you?" cancel link, since that's the one channel the real owner
     * still controls if the account has actually been compromised.
     */
    public function requestEmailChange(User $user, string $newEmail): void
    {
        $token = Str::random(64);
        $oldEmail = $user->email;

        $user->update([
            'pending_email' => $newEmail,
            'pending_email_token' => Hash::make($token),
            'pending_email_expires_at' => now()->addHours(self::PENDING_EMAIL_TTL_HOURS),
        ]);

        Mail::to($newEmail)->send(new AccountEmailChangeMail($user, $newEmail, $token));
        Mail::to($oldEmail)->send(new AccountEmailChangeNoticeMail($user, $newEmail, $token));
    }

    public function cancelEmailChange(User $user): void
    {
        $user->update([
            'pending_email' => null,
            'pending_email_token' => null,
            'pending_email_expires_at' => null,
        ]);
    }

    /**
     * Cancels a pending change via the token from the "wasn't you?" notice
     * mailed to the OLD address — deliberately callable without an
     * authenticated session, since the real owner may already be locked out.
     * Returns false (rather than throwing) for an invalid/stale/already-used
     * link so the controller can show a calm "already handled" message.
     */
    public function cancelEmailChangeWithToken(User $user, string $token): bool
    {
        if (
            ! $user->pending_email
            || ! $user->pending_email_token
            || ! Hash::check($token, $user->pending_email_token)
        ) {
            return false;
        }

        $this->cancelEmailChange($user);

        return true;
    }

    /** @throws ValidationException if the token is wrong/expired/stale */
    public function confirmEmailChange(User $user, string $token): void
    {
        if (
            ! $user->pending_email
            || ! $user->pending_email_token
            || ! $user->pending_email_expires_at
            || $user->pending_email_expires_at->isPast()
            || ! Hash::check($token, $user->pending_email_token)
        ) {
            throw ValidationException::withMessages([
                'token' => [__('This email confirmation link is invalid or has expired. Request a new one from your account page.')],
            ]);
        }

        $user->update([
            'email' => $user->pending_email,
            'email_verified_at' => now(),
            'pending_email' => null,
            'pending_email_token' => null,
            'pending_email_expires_at' => null,
        ]);
    }

    // ── Two-factor (TOTP) ────────────────────────────────────────────────────

    /** New secret for setup — NOT saved yet; the controller keeps it in session until confirmed. */
    public function generateSecret(): string
    {
        return (new Google2FA)->generateSecretKey();
    }

    public function qrCodeUrl(User $user, string $secret): string
    {
        return (new Google2FA)->getQRCodeUrl(
            config('app.name', 'School'),
            $user->email,
            $secret,
        );
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return (new Google2FA)->verifyKey($secret, $code) !== false;
    }

    /** Confirms setup: verifies the code against the not-yet-saved secret, then saves secret + fresh recovery codes. */
    public function confirmTwoFactor(User $user, string $secret, string $code): array
    {
        if (! $this->verifyCode($secret, $code)) {
            throw ValidationException::withMessages([
                'code' => [__('That code is invalid or has expired. Check your authenticator app and try again.')],
            ]);
        }

        $recoveryCodes = $this->freshRecoveryCodes();

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ]);

        return $recoveryCodes;
    }

    public function disableTwoFactor(User $user, string $currentPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('Your current password is incorrect.')],
            ]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->freshRecoveryCodes();
        $user->update(['two_factor_recovery_codes' => $codes]);

        return $codes;
    }

    /** Checks + burns a recovery code (one-time use). */
    public function redeemRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $code = strtoupper(trim($code));

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $user->update([
            'two_factor_recovery_codes' => array_values(array_diff($codes, [$code])),
        ]);

        return true;
    }

    private function freshRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODE_COUNT))
            ->map(fn () => strtoupper(Str::random(4).'-'.Str::random(4)))
            ->all();
    }
}
