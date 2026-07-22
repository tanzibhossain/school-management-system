{{-- Shared account & security form, included by admin/staff/portal account pages.
     Expects: $portalPrefix, $accountUser, $activeSessions, $currentSessionId --}}
<div class="row g-4">
  <div class="col-lg-6">
    {{-- Name --}}
    <div class="card mb-4">
      <div class="card-header">{{ __('Your name') }}</div>
      <div class="card-body">
        <form method="POST" action="{{ route("$portalPrefix.account.update-name") }}">
          @csrf @method('PUT')
          <div class="mb-3">
            <input type="text" name="name" class="form-control" value="{{ old('name', $accountUser->name) }}" required>
          </div>
          <button class="btn btn-primary btn-sm">{{ __('Save') }}</button>
        </form>
      </div>
    </div>

    {{-- Password --}}
    <div class="card mb-4">
      <div class="card-header">{{ __('Change password') }}</div>
      <div class="card-body">
        <form method="POST" action="{{ route("$portalPrefix.account.update-password") }}">
          @csrf @method('PUT')
          <div class="mb-3">
            <label class="form-label small">{{ __('Current password') }}</label>
            <input type="password" name="current_password" class="form-control" autocomplete="current-password" required>
          </div>
          <div class="mb-3">
            <label class="form-label small">{{ __('New password') }}</label>
            <input type="password" name="password" class="form-control" autocomplete="new-password" required>
          </div>
          <div class="mb-3">
            <label class="form-label small">{{ __('Confirm new password') }}</label>
            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
          </div>
          <button class="btn btn-primary btn-sm">{{ __('Update password') }}</button>
        </form>
      </div>
    </div>

    {{-- Email --}}
    <div class="card mb-4">
      <div class="card-header">{{ __('Email address') }}</div>
      <div class="card-body">
        <p class="mb-3">{{ __('Current') }}: <strong>{{ $accountUser->email }}</strong></p>

        @if($accountUser->pending_email)
          <div class="alert alert-warning py-2 small">
            {{ __('Pending change to') }} <strong>{{ $accountUser->pending_email }}</strong> —
            {{ __('check that inbox for a confirmation link. Expires') }} {{ $accountUser->pending_email_expires_at?->diffForHumans() }}.
          </div>
          <form method="POST" action="{{ route("$portalPrefix.account.cancel-email") }}">
            @csrf @method('DELETE')
            <button class="btn btn-outline-secondary btn-sm">{{ __('Cancel pending change') }}</button>
          </form>
        @else
          <form method="POST" action="{{ route("$portalPrefix.account.request-email") }}">
            @csrf
            <div class="mb-3">
              <label class="form-label small">{{ __('New email address') }}</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <button class="btn btn-primary btn-sm">{{ __('Send confirmation link') }}</button>
          </form>
        @endif
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    {{-- Two-factor --}}
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ __('Two-factor authentication') }}</span>
        @if($accountUser->hasTwoFactorEnabled())
          <span class="badge text-bg-success">{{ __('Enabled') }}</span>
        @else
          <span class="badge text-bg-secondary">{{ __('Disabled') }}</span>
        @endif
      </div>
      <div class="card-body">
        @if(session('recovery_codes'))
          <div class="alert alert-warning small">
            <div class="fw-semibold mb-2">{{ __('Save these recovery codes somewhere safe') }}</div>
            <p class="mb-2">{{ __('Each can be used once to sign in if you lose access to your authenticator app. They will not be shown again.') }}</p>
            <div class="d-flex flex-wrap gap-2 font-monospace">
              @foreach(session('recovery_codes') as $rc)
                <span class="badge text-bg-light border">{{ $rc }}</span>
              @endforeach
            </div>
          </div>
        @endif

        @if($accountUser->hasTwoFactorEnabled())
          <p class="text-muted small">{{ __('Your account is protected by an authenticator app.') }}</p>
          <form method="POST" action="{{ route("$portalPrefix.account.2fa.recovery-codes") }}" class="mb-3">
            @csrf
            <button class="btn btn-outline-secondary btn-sm">{{ __('Regenerate recovery codes') }}</button>
          </form>
          <form method="POST" action="{{ route("$portalPrefix.account.2fa.disable") }}">
            @csrf @method('DELETE')
            <div class="mb-2">
              <label class="form-label small">{{ __('Current password') }}</label>
              <input type="password" name="current_password" class="form-control form-control-sm" style="max-width:260px" required>
            </div>
            <button class="btn btn-outline-danger btn-sm">{{ __('Disable two-factor authentication') }}</button>
          </form>
        @else
          <p class="text-muted small">{{ __('Add an extra layer of security — after your password, you\'ll also need a code from an authenticator app (Google Authenticator, Authy, etc.).') }}</p>
          <a href="{{ route("$portalPrefix.account.2fa.enable") }}" class="btn btn-primary btn-sm">{{ __('Enable two-factor authentication') }}</a>
        @endif
      </div>
    </div>

    {{-- Sessions --}}
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ __('Signed-in devices') }}</span>
        @if($activeSessions->count() > 1)
          <form method="POST" action="{{ route("$portalPrefix.account.sessions.revoke-others") }}">
            @csrf
            <button class="btn btn-outline-danger btn-sm">{{ __('Sign out all other sessions') }}</button>
          </form>
        @endif
      </div>
      <div class="list-group list-group-flush">
        @forelse($activeSessions as $s)
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-medium small">
                {{ $s->device_name ?? __('Unknown device') }}
                @if($s->session_id === $currentSessionId)
                  <span class="badge text-bg-success ms-1">{{ __('This device') }}</span>
                @endif
              </div>
              <div class="text-muted" style="font-size:.78rem;">
                {{ $s->ip_address }} — {{ __('Signed in') }} {{ $s->logged_in_at?->diffForHumans() }}
              </div>
            </div>
            @if($s->session_id !== $currentSessionId)
              <form method="POST" action="{{ route("$portalPrefix.account.sessions.revoke", $s->id) }}">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">{{ __('Sign out') }}</button>
              </form>
            @endif
          </div>
        @empty
          <div class="list-group-item text-muted small">{{ __('No other active sessions.') }}</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
