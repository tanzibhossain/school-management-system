@php
  $primary  = $settings->primary_color ?? '#1d4ed8';
  $topText  = $settings->topbar_text_color ?? '#ffffff';
  $siteName = $settings->site_name ?? ($school->name ?? 'Our School');

  $loc = $school?->locale ?? app()->getLocale();
  $tz  = $school?->timezone ?? config('app.timezone');
  try { $today = \Illuminate\Support\Carbon::now($tz)->locale($loc); } catch (\Throwable $e) { $today = now()->locale($loc); }
  $dateStr = $today->translatedFormat('l j F Y');
  if (str_starts_with((string) $loc, 'bn')) {
      $dateStr = strtr($dateStr, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']);
  }

  $welcome = $settings->topbar_welcome ?: ('Welcome to ' . $siteName);
  $phone   = $settings->topbar_phone;
  $telHref = $phone ? 'tel:' . preg_replace('/[^0-9+]/', '', explode(',', $phone)[0]) : null;
  $established = $school?->established ? (optional($school->established)->format('Y') ?? $school->established) : null;
@endphp

{{-- Row 1: top utility bar (primary bg, configurable text colour) --}}
<div style="background: {{ $primary }}; color: {{ $topText }};">
  <div class="container">
    <div class="row align-items-center g-2 py-1 small" style="min-height:30px;">
      <div class="col-md-5 text-center text-md-start">{{ $welcome }}</div>
      <div class="col-md-4 text-center text-capitalize">{{ $dateStr }}</div>
      <div class="col-md-3 text-center text-md-end">
        @if($phone)
          <a href="{{ $telHref }}" style="color: {{ $topText }}; text-decoration:none;"><i class="bi bi-telephone-fill"></i> {{ $phone }}</a>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Row 2: logo | name | institution data --}}
<div class="bg-white border-bottom">
  <div class="container py-3">
    <div class="row align-items-center g-3">
      <div class="col-4 col-md-2 text-center text-md-start">
        <a href="{{ route('home') }}">
          @if($school?->logo)<img src="{{ $school->logo }}" alt="{{ $siteName }}" style="max-height:72px;max-width:100%;">
          @else<i class="bi bi-mortarboard-fill display-5" style="color: {{ $primary }};"></i>@endif
        </a>
      </div>
      <div class="col-8 col-md-6">
        <a href="{{ route('home') }}" class="text-decoration-none">
          <div class="h4 fw-bold mb-0" style="color: {{ $primary }};">{{ $siteName }}</div>
        </a>
        @if($school?->address)<div class="text-muted small">{{ $school->address }}</div>@endif
      </div>
      <div class="col-12 col-md-4 small text-muted text-md-end">
        @if($school?->institution_code)<div>{{ $school->institution_code_label ?? 'EIIN' }}: <strong>{{ $school->institution_code }}</strong></div>@endif
        @if($school?->technical_branch_code)<div>Technical code: <strong>{{ $school->technical_branch_code }}</strong></div>@endif
        @if($school?->school_code)<div>School code: <strong>{{ $school->school_code }}</strong></div>@endif
        @if($established)<div>Established: <strong>{{ $established }}</strong></div>@endif
      </div>
    </div>
  </div>
</div>

{{-- Row 3: navigation --}}
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: {{ $primary }};">
  <div class="container">
    <button class="navbar-toggler ms-auto" data-bs-toggle="collapse" data-bs-target="#pubnav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="pubnav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Home</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">About</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ url('/history') }}">Short history</a></li>
            <li><a class="dropdown-item" href="{{ url('/about') }}">At a glance</a></li>
            <li><a class="dropdown-item" href="{{ url('/mission') }}">Mission &amp; vision</a></li>
            <li><a class="dropdown-item" href="{{ url('/administration') }}">Administration</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Staff</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ url('/staff') }}">All staff</a></li>
            <li><a class="dropdown-item" href="{{ url('/teachers') }}">Teachers</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="{{ url('/online-admission') }}">Online admission</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Gallery</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ url('/gallery') }}">Photo gallery</a></li>
            <li><a class="dropdown-item" href="{{ url('/video') }}">Video gallery</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="{{ url('/contact') }}">Contact</a></li>
      </ul>
      <a class="btn btn-light btn-sm px-3" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i> Login</a>
    </div>
  </div>
</nav>

{{-- Notice ticker --}}
@if(($ticker ?? collect())->isNotEmpty())
  <div class="border-bottom bg-light">
    <div class="container d-flex align-items-center gap-2 py-1" style="overflow:hidden;">
      <a href="{{ url('/notices') }}" class="badge text-bg-danger text-decoration-none flex-shrink-0"><i class="bi bi-megaphone-fill"></i> Notice</a>
      <div class="pub-ticker flex-grow-1">
        <div class="pub-ticker-track">
          @foreach($ticker as $t)
            <a href="{{ url('/notices') }}" class="text-decoration-none text-dark me-5">{{ $t->title }}</a>
          @endforeach
        </div>
      </div>
    </div>
  </div>
@endif
