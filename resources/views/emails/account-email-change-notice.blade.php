<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ __('Your account email is being changed') }}</title>
</head>
<body style="margin:0; padding:0; background:#f1f3f9; font-family: -apple-system, Segoe UI, Roboto, sans-serif;">
  <div style="max-width:480px; margin:2rem auto; background:#fff; border-radius:12px; padding:2rem; color:#0f172a;">
    <p>{{ __('Hi') }} {{ $userName }},</p>
    <p>{{ __('Someone requested to change the email address on your account to :email. If this was you, no action is needed — the change only takes effect once that new address confirms it.', ['email' => $newEmail]) }}</p>
    <p style="text-align:center; margin:2rem 0;">
      <a href="{{ $cancelUrl }}" style="background:#dc2626; color:#fff; text-decoration:none; padding:.75rem 1.5rem; border-radius:8px; font-weight:600; display:inline-block;">
        {{ __("It wasn't me — cancel this change") }}
      </a>
    </p>
    <p style="color:#64748b; font-size:.9rem;">{{ __('This link expires in 24 hours and works even if you can no longer sign in. If you cancel it, also change your password right away.') }}</p>
  </div>
</body>
</html>
