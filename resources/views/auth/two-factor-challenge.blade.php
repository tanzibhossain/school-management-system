<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ __('Two-Factor Verification') }}</title>
  <link rel="icon" href="{{ asset('favicon.ico') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    body { margin: 0; background: #f1f3f9; color: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .card { width: 100%; max-width: 400px; border: 0; border-radius: 14px; box-shadow: 0 10px 30px rgba(15,23,42,.08); }
    .btn-brand { background: #4f46e5; border: 0; color: #fff; }
    .btn-brand:hover { background: #4338ca; color: #fff; }
  </style>
</head>
<body>
  <div class="card p-4">
    <div class="card-body">
      <div class="mb-3 text-center">
        <i class="bi bi-shield-lock display-6 text-primary"></i>
      </div>
      <h2 class="h5 text-center mb-1">{{ __('Two-Factor Verification') }}</h2>
      <p class="text-muted text-center small mb-4">{{ __('Enter the 6-digit code from your authenticator app, or one of your recovery codes.') }}</p>

      @if ($errors->any())
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}</div>
      @endif

      <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf
        <div class="mb-3">
          <input type="text" name="code" class="form-control form-control-lg text-center" style="letter-spacing:.3em;" inputmode="numeric" autofocus autocomplete="one-time-code" placeholder="000000" required>
        </div>
        <button class="btn btn-brand w-100 py-2 fw-semibold">{{ __('Verify') }}</button>
      </form>

      <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="small text-muted text-decoration-none">{{ __('Cancel and sign in as someone else') }}</a>
      </div>
    </div>
  </div>
</body>
</html>
