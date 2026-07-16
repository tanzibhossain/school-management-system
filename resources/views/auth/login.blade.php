@php
    $cfg = [
        'admin'  => ['label' => 'Administrator', 'desc' => 'Management console', 'icon' => 'bi-shield-lock-fill', 'action' => route('admin.login')],
        'staff'  => ['label' => 'Staff & Teachers', 'desc' => 'Teaching & staff portal', 'icon' => 'bi-mortarboard-fill', 'action' => route('staff.login')],
        'family' => ['label' => 'Student & Guardian', 'desc' => 'Family portal', 'icon' => 'bi-people-fill', 'action' => route('login')],
    ];
    $portal = $portal ?? 'family';
    $current = $cfg[$portal];
    $others = collect($cfg)->except($portal);
    $schoolName = $school->name ?? 'School Management';
    $logoUrl = ($school && $school->logo) ? \App\Support\Media::url($school->logo) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in · {{ $current['label'] }} · {{ $schoolName }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --brand: #4f46e5; --brand-dark: #4338ca; --ink: #0f172a; --muted: #64748b; }
    * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    body { margin: 0; background: #f1f3f9; color: var(--ink); }
    .auth-wrap { min-height: 100vh; display: grid; grid-template-columns: 1.05fr 1fr; }
    /* Brand panel */
    .auth-brand {
      position: relative; color: #fff; padding: 3rem;
      display: flex; flex-direction: column; justify-content: space-between;
      background: radial-gradient(1200px 500px at -10% -10%, #6366f1 0%, transparent 60%), linear-gradient(135deg, #4f46e5, #3730a3);
      overflow: hidden;
    }
    .auth-brand::after {
      content: ''; position: absolute; right: -120px; bottom: -120px;
      width: 360px; height: 360px; border-radius: 50%; background: rgba(255,255,255,.08);
    }
    .auth-brand-logo { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.15);
      display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; overflow: hidden; }
    .auth-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
    .auth-brand h1 { font-size: 2rem; font-weight: 700; line-height: 1.15; margin: 1.5rem 0 .75rem; }
    .auth-brand p { color: rgba(255,255,255,.82); max-width: 26rem; }
    .auth-portals { display: flex; flex-direction: column; gap: .5rem; position: relative; z-index: 1; }
    .auth-portal-chip {
      display: flex; align-items: center; gap: .75rem; padding: .7rem .9rem; border-radius: 12px;
      color: #fff; text-decoration: none; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14);
      transition: background .15s;
    }
    .auth-portal-chip:hover { background: rgba(255,255,255,.16); color: #fff; }
    .auth-portal-chip.is-current { background: #fff; color: var(--brand); border-color: #fff; font-weight: 600; }
    .auth-portal-chip small { display: block; opacity: .7; font-weight: 400; }
    .auth-portal-chip.is-current small { color: var(--muted); opacity: 1; }

    /* Form panel */
    .auth-form-panel { display: flex; align-items: center; justify-content: center; padding: 2rem; }
    .auth-card { width: 100%; max-width: 400px; }
    .auth-eyebrow { display: inline-flex; align-items: center; gap: .5rem; font-size: .8rem; font-weight: 600;
      color: var(--brand); background: #eef2ff; padding: .35rem .7rem; border-radius: 999px; }
    .auth-card h2 { font-weight: 700; margin: 1rem 0 .25rem; }
    .auth-card .sub { color: var(--muted); margin-bottom: 1.5rem; }
    .form-label { font-weight: 500; font-size: .9rem; }
    .form-control { padding: .6rem .8rem; border-radius: 10px; border-color: #e2e8f0; }
    .form-control:focus { border-color: #a5b4fc; box-shadow: 0 0 0 .25rem rgba(79,70,229,.18); }
    .btn-brand { background: var(--brand); border: 0; color: #fff; padding: .65rem; border-radius: 10px; font-weight: 600; }
    .btn-brand:hover { background: var(--brand-dark); color: #fff; }
    .auth-switch { font-size: .85rem; color: var(--muted); }
    .auth-switch a { color: var(--brand); text-decoration: none; font-weight: 500; }
    @media (max-width: 860px) {
      .auth-wrap { grid-template-columns: 1fr; }
      .auth-brand { display: none; }
    }
  </style>
</head>
<body>
  <div class="auth-wrap">
    <!-- Brand / portal switcher -->
    <aside class="auth-brand">
      <div>
        <span class="auth-brand-logo">
          @if($logoUrl)<img src="{{ $logoUrl }}" alt="">@else<i class="bi bi-mortarboard-fill"></i>@endif
        </span>
        <h1>{{ $schoolName }}</h1>
        <p>Sign in to your portal. Each role has its own workspace tailored to what you do every day.</p>
      </div>
      <div class="auth-portals">
        <div class="auth-portal-chip is-current">
          <i class="bi {{ $current['icon'] }} fs-5"></i>
          <span>{{ $current['label'] }}<small>{{ $current['desc'] }}</small></span>
        </div>
        @foreach($others as $key => $o)
          <a href="{{ $o['action'] }}" class="auth-portal-chip">
            <i class="bi {{ $o['icon'] }} fs-5"></i>
            <span>{{ $o['label'] }}<small>{{ $o['desc'] }}</small></span>
          </a>
        @endforeach
      </div>
    </aside>

    <!-- Form -->
    <main class="auth-form-panel">
      <div class="auth-card">
        <span class="auth-eyebrow"><i class="bi {{ $current['icon'] }}"></i> {{ $current['label'] }}</span>
        <h2>Welcome back</h2>
        <p class="sub">{{ $current['desc'] }} — sign in to continue.</p>

        @if ($errors->any())
          <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ $current['action'] }}">
          @csrf
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
          </div>
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="form-check mb-0">
              <input type="checkbox" name="remember" class="form-check-input" id="remember">
              <label class="form-check-label small" for="remember">Remember me</label>
            </div>
          </div>
          <button class="btn btn-brand w-100">Sign in <i class="bi bi-arrow-right ms-1"></i></button>
        </form>

        <div class="text-center mt-4 auth-switch d-md-none">
          @foreach($others as $o)
            <a href="{{ $o['action'] }}">{{ $o['label'] }}</a>@if(!$loop->last) · @endif
          @endforeach
        </div>
      </div>
    </main>
  </div>
</body>
</html>
