<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Portal') · {{ optional(\App\Modules\School\Models\School::first())->name ?? 'School' }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --sidebar-width: 260px; --header-height: 62px;
      --brand: #4f46e5; --brand-dark: #4338ca; --brand-tint: #eef2ff;
      --sb-border: #e8ecf1; --sb-text: #374151; --sb-muted: #6b7280; --sb-hover: #f8fafc;
    }
    * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    body { background: #f5f7fb; }
    .btn-primary { --bs-btn-bg:#4f46e5; --bs-btn-border-color:#4f46e5; --bs-btn-hover-bg:#4338ca; --bs-btn-hover-border-color:#4338ca; }
    .text-primary { color:#4f46e5 !important; } .bg-primary { background-color:#4f46e5 !important; }
    a { color:#4f46e5; }
    .card { border:1px solid #eef0f4; box-shadow:0 1px 2px rgba(16,24,40,.05); border-radius:12px; }
    .card-header { background:#fff; font-weight:600; }

    .sidebar { width:var(--sidebar-width); min-height:100vh; position:fixed; top:0; left:0; z-index:1040;
      background:#fff; border-right:1px solid var(--sb-border); display:flex; flex-direction:column; }
    .sidebar-header { height:var(--header-height); display:flex; align-items:center; gap:.7rem; padding:0 1rem; border-bottom:1px solid var(--sb-border); }
    .sidebar-brand { display:flex; align-items:center; gap:.6rem; text-decoration:none; color:#0f172a; font-weight:700; }
    .brand-icon { width:36px; height:36px; border-radius:10px; background:var(--brand-tint); color:var(--brand); display:inline-flex; align-items:center; justify-content:center; font-size:1.2rem; }
    .sidebar-nav { flex:1; overflow-y:auto; padding:.75rem .5rem; }
    .nav-link { color:var(--sb-text); border-radius:8px; margin:.1rem .25rem; padding:.6rem .75rem; display:flex; align-items:center; gap:.7rem; font-size:.9rem; font-weight:500; }
    .nav-link:hover { background:var(--sb-hover); color:var(--brand); }
    .nav-link.active { background:#eef1f5; color:#111827; font-weight:600; }
    .nav-link .bi { width:1.25rem; text-align:center; font-size:1.05rem; }
    .nav-link.active .bi, .nav-link:hover .bi { color:var(--brand); }
    .sidebar-footer { border-top:1px solid var(--sb-border); padding:.75rem; }

    .content { margin-left:var(--sidebar-width); min-height:100vh; }
    .page-head { background:#fff; border-bottom:1px solid var(--sb-border); min-height:var(--header-height); position:sticky; top:0; z-index:1030; }
    .avatar-sm { width:34px; height:34px; border-radius:50%; background:var(--brand-tint); color:var(--brand); display:inline-flex; align-items:center; justify-content:center; font-weight:600; }

    .sidebar-backdrop { display:none; }
    @media (max-width: 991px) {
      .sidebar { transform:translateX(-100%); transition:transform .2s; }
      .sidebar.show { transform:none; }
      .content { margin-left:0 !important; }
      .sidebar-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:1039; }
    }
  </style>
</head>
<body>
  @php
    $pu = auth()->user();
    $students = $students ?? collect();
    $student = $student ?? null;
    $isGuardian = $isGuardian ?? false;
    $link = fn ($r) => route($r, $student ? ['student' => $student->id] : []);
  @endphp

  <aside class="sidebar" id="pSidebar">
    <div class="sidebar-header">
      <a href="{{ route('portal.dashboard') }}" class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-people-fill"></i></span>
        <span>{{ __('Family Portal') }}</span>
      </a>
      <button class="btn btn-sm ms-auto d-lg-none" id="pSidebarClose"><i class="bi bi-x-lg"></i></button>
    </div>
    <nav class="sidebar-nav">
      <a href="{{ $link('portal.dashboard') }}" class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> {{ __('Dashboard') }}</a>
      <a href="{{ $link('portal.attendance') }}" class="nav-link {{ request()->routeIs('portal.attendance') ? 'active' : '' }}"><i class="bi bi-calendar-check"></i> {{ __('Attendance') }}</a>
      <a href="{{ $link('portal.results') }}" class="nav-link {{ request()->routeIs('portal.results') ? 'active' : '' }}"><i class="bi bi-award"></i> {{ __('Results') }}</a>
      <a href="{{ $link('portal.fees') }}" class="nav-link {{ request()->routeIs('portal.fees') ? 'active' : '' }}"><i class="bi bi-receipt"></i> {{ __('Fees') }}</a>
      <a href="{{ $link('portal.routine') }}" class="nav-link {{ request()->routeIs('portal.routine') ? 'active' : '' }}"><i class="bi bi-calendar3-week"></i> {{ __('Class Routine') }}</a>
      <a href="{{ $link('portal.leave') }}" class="nav-link {{ request()->routeIs('portal.leave*') ? 'active' : '' }}"><i class="bi bi-calendar-minus"></i> {{ __('Leave') }}</a>
      <a href="{{ $link('portal.notices') }}" class="nav-link {{ request()->routeIs('portal.notices') ? 'active' : '' }}"><i class="bi bi-megaphone"></i> {{ __('Notices') }}</a>
      <a href="{{ route('portal.messages') }}" class="nav-link {{ request()->routeIs('portal.messages*') ? 'active' : '' }}"><i class="bi bi-chat-left-text"></i> Messages
        @if(($messagesUnread ?? 0) > 0)<span class="badge text-bg-primary rounded-pill ms-auto">{{ $messagesUnread }}</span>@endif
      </a>
      <a href="{{ $link('portal.profile') }}" class="nav-link {{ request()->routeIs('portal.profile') ? 'active' : '' }}"><i class="bi bi-person-vcard"></i> {{ __('Profile') }}</a>
      <a href="{{ route('portal.account') }}" class="nav-link {{ request()->routeIs('portal.account*') ? 'active' : '' }}"><i class="bi bi-shield-lock"></i> {{ __('Account & Security') }}</a>
    </nav>
    <div class="sidebar-footer">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-box-arrow-right me-1"></i> {{ __('Sign Out') }}</button>
      </form>
    </div>
  </aside>
  <div class="sidebar-backdrop" id="pBackdrop"></div>

  <div class="content">
    <header class="page-head">
      <div class="d-flex align-items-center h-100 px-3 px-lg-4" style="min-height:var(--header-height)">
        <button class="btn btn-sm d-lg-none me-2" id="pSidebarToggle"><i class="bi bi-list fs-5"></i></button>
        <div class="fw-semibold">@yield('heading', 'Dashboard')</div>

        <div class="ms-auto d-flex align-items-center gap-3">
          @include('partials.language-switcher', ['linkClass' => 'small text-muted'])
          {{-- Child switcher for guardians with more than one child --}}
          @if($isGuardian && $students->count() > 1)
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-person me-1"></i>{{ $student->name ?? 'Select child' }}
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                @foreach($students as $s)
                  <li><a class="dropdown-item {{ $student && $s->id === $student->id ? 'active' : '' }}" href="{{ route(request()->route()->getName(), ['student' => $s->id]) }}">{{ $s->name }}</a></li>
                @endforeach
              </ul>
            </div>
          @endif
          <div class="text-end d-none d-sm-block">
            <div class="fw-medium small">{{ $pu?->name }}</div>
            <div class="text-muted" style="font-size:.72rem; text-transform:capitalize;">{{ $isGuardian ? 'Guardian' : 'Student' }}</div>
          </div>
          <span class="avatar-sm">{{ strtoupper(substr($pu?->name ?? 'U', 0, 1)) }}</span>
        </div>
      </div>
    </header>

    <main class="p-3 p-lg-4" style="max-width:1100px; margin:0 auto;">
      @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
      @endif
      @yield('content')
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      var sb = document.getElementById('pSidebar'), bd = document.getElementById('pBackdrop');
      function open(v){ sb.classList.toggle('show', v); bd.style.display = v ? 'block' : 'none'; }
      document.getElementById('pSidebarToggle')?.addEventListener('click', function(){ open(true); });
      document.getElementById('pSidebarClose')?.addEventListener('click', function(){ open(false); });
      bd?.addEventListener('click', function(){ open(false); });
    })();
  </script>
  @stack('scripts')
</body>
</html>
