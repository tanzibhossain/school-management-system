<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  @php
    $s = $settings ?? null;
    $primary = $s->primary_color ?? '#1d4ed8';
    $accent  = $s->accent_color ?? '#f59e0b';
    $heading = $s->heading_color ?? '#0f172a';
    $siteName = $s->site_name ?? ($school->name ?? 'Our School');
  @endphp
  <title>@yield('title', $siteName)</title>
  @if ($s?->meta_desc ?? false)<meta name="description" content="{{ $s->meta_desc }}">@endif
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root { --brand:{{ $primary }}; --brand-accent:{{ $accent }}; --brand-heading:{{ $heading }}; }
    body { color:#1f2937; }
    a { color:var(--brand); }
    .navbar-brand { font-weight:700; color:var(--brand) !important; }
    .btn-brand { background:var(--brand); border-color:var(--brand); color:#fff; }
    .btn-brand:hover { filter:brightness(.92); color:#fff; }
    .text-brand { color:var(--brand); }
    .hero { background:linear-gradient(135deg, var(--brand), color-mix(in srgb, var(--brand) 65%, #000)); color:#fff; }
    .hero h1 { color:#fff; font-weight:700; }
    .section-title { color:var(--brand-heading); font-weight:700; }
    .stat-num { color:var(--brand); font-weight:700; font-size:2.25rem; line-height:1; }
    .card { border:0; box-shadow:0 1px 2px rgba(16,24,40,.06),0 1px 3px rgba(16,24,40,.05); }
    footer { background:#0f172a; color:#cbd5e1; }
    footer a { color:#e2e8f0; text-decoration:none; }
    .pub-ticker { overflow:hidden; white-space:nowrap; }
    .pub-ticker-track { display:inline-block; padding-left:100%; animation:pub-ticker 28s linear infinite; }
    .pub-ticker:hover .pub-ticker-track { animation-play-state:paused; }
    @keyframes pub-ticker { 0%{transform:translateX(0);} 100%{transform:translateX(-100%);} }
    @if ($s?->custom_css ?? false){!! $s->custom_css !!}@endif
  </style>
</head>
<body>
  @include('public.partials.header')

  @yield('content')

  <footer class="py-5 mt-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-5">
          <h5 class="text-white mb-2">{{ $siteName }}</h5>
          @if ($school?->address ?? false)<p class="mb-1 small"><i class="bi bi-geo-alt"></i> {{ $school->address }}</p>@endif
          @if ($school?->email ?? false)<p class="mb-0 small"><i class="bi bi-envelope"></i> {{ $school->email }}</p>@endif
        </div>
        <div class="col-md-4">
          <h6 class="text-white-50 text-uppercase small mb-2">Quick links</h6>
          <div class="d-flex flex-column gap-1 small">
            <a href="{{ route('home') }}#notices">Notices</a>
            <a href="{{ route('home') }}#results">Check results</a>
            <a href="{{ route('login') }}">Portal login</a>
          </div>
        </div>
        <div class="col-md-3">
          <h6 class="text-white-50 text-uppercase small mb-2">Portal</h6>
          <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Sign in</a>
        </div>
      </div>
      <hr class="border-secondary my-4">
      <p class="small mb-0 text-center text-white-50">© {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
