<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ __('Confirm your new email address') }}</title>
</head>
<body style="margin:0; padding:0; background:#f1f3f9; font-family: -apple-system, Segoe UI, Roboto, sans-serif;">
  <div style="max-width:480px; margin:2rem auto; background:#fff; border-radius:12px; padding:2rem; color:#0f172a;">
    <p>{{ __('Hi') }} {{ $userName }},</p>
    <p>{{ __('You (or someone with access to your account) requested to change the email address on this account to this one. Confirm the change by clicking the button below.') }}</p>
    <p style="text-align:center; margin:2rem 0;">
      <a href="{{ $confirmUrl }}" style="background:#4f46e5; color:#fff; text-decoration:none; padding:.75rem 1.5rem; border-radius:8px; font-weight:600; display:inline-block;">
        {{ __('Confirm email address') }}
      </a>
    </p>
    <p style="color:#64748b; font-size:.9rem;">{{ __('This link expires in 24 hours. If you did not request this change, you can safely ignore this email — your account email will not change.') }}</p>
  </div>
</body>
</html>
