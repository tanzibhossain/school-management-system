{{-- Shared 2FA setup form. Expects: $portalPrefix, $secret, $qrUrl --}}
<div class="row g-4">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">{{ __('1. Scan this with your authenticator app') }}</div>
      <div class="card-body text-center">
        <div id="twoFactorQr" class="d-inline-block p-2 bg-white border rounded mb-3"></div>
        <p class="text-muted small mb-1">{{ __("Can't scan? Enter this code manually:") }}</p>
        <code class="d-inline-block bg-light border rounded px-2 py-1">{{ chunk_split($secret, 4, ' ') }}</code>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">{{ __('2. Enter the 6-digit code it shows') }}</div>
      <div class="card-body">
        @if ($errors->any())
          <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route("$portalPrefix.account.2fa.confirm") }}">
          @csrf
          <div class="mb-3">
            <input type="text" name="code" class="form-control form-control-lg text-center" style="letter-spacing:.3em; max-width:220px;" inputmode="numeric" autocomplete="one-time-code" autofocus placeholder="000000" required>
          </div>
          <button class="btn btn-primary">{{ __('Verify & enable') }}</button>
          <a href="{{ route("$portalPrefix.account") }}" class="btn btn-link">{{ __('Cancel') }}</a>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
  new QRCode(document.getElementById('twoFactorQr'), {
    text: @json($qrUrl),
    width: 200,
    height: 200,
  });
</script>
@endpush
