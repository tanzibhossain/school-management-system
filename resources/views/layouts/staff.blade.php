<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Staff') · {{ optional(\App\Modules\School\Models\School::first())->name ?? 'School' }}</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --sidebar-width: 260px; --header-height: 62px;
      --brand: #4f46e5; --brand-dark: #4338ca; --brand-tint: #eef2ff;
      --sb-bg: #fff; --sb-border: #e8ecf1; --sb-text: #374151; --sb-muted: #6b7280; --sb-hover: #f8fafc;
    }
    * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    body { background: #f5f7fb; }

    /* Indigo accent for Bootstrap */
    .btn-primary { --bs-btn-bg:#4f46e5; --bs-btn-border-color:#4f46e5; --bs-btn-hover-bg:#4338ca; --bs-btn-hover-border-color:#4338ca; }
    .text-primary { color:#4f46e5 !important; } .bg-primary { background-color:#4f46e5 !important; }
    a { color: #4f46e5; }
    .card { border: 1px solid #eef0f4; box-shadow: 0 1px 2px rgba(16,24,40,.05); border-radius: 12px; }
    .card-header { background:#fff; font-weight:600; }

    /* Sidebar */
    .sidebar { width: var(--sidebar-width); min-height: 100vh; position: fixed; top:0; left:0; z-index:1040;
      background: var(--sb-bg); border-right: 1px solid var(--sb-border); display:flex; flex-direction:column; }
    .sidebar-header { height: var(--header-height); display:flex; align-items:center; gap:.7rem; padding: 0 1rem; border-bottom:1px solid var(--sb-border); }
    .sidebar-brand { display:flex; align-items:center; gap:.6rem; text-decoration:none; color:#0f172a; font-weight:700; }
    .brand-icon { width:36px; height:36px; border-radius:10px; background: var(--brand-tint); color: var(--brand); display:inline-flex; align-items:center; justify-content:center; font-size:1.2rem; }
    .sidebar-nav { flex:1; overflow-y:auto; padding:.75rem .5rem; }
    .sidebar-nav .section-label { font-size:.68rem; letter-spacing:.06em; text-transform:uppercase; color:var(--sb-muted); font-weight:700; padding:.5rem .75rem .25rem; }
    .nav-link { color:var(--sb-text); border-radius:8px; margin:.1rem .25rem; padding:.6rem .75rem; display:flex; align-items:center; gap:.7rem; font-size:.9rem; font-weight:500; text-transform:capitalize; }
    .nav-link:hover { background:var(--sb-hover); color:var(--brand); }
    .nav-link.active { background:#eef1f5; color:#111827; font-weight:600; }
    .nav-link.disabled { color:#adb5bd; pointer-events:none; }
    .nav-link .bi { width:1.25rem; text-align:center; font-size:1.05rem; }
    .nav-link.active .bi, .nav-link:hover .bi { color: var(--brand); }
    .soon-badge { font-size:.6rem; background:#eef1f5; color:#94a3b8; padding:.1rem .35rem; border-radius:6px; margin-left:auto; }
    .sidebar-footer { border-top:1px solid var(--sb-border); padding:.75rem; }

    /* Header + content */
    .content { margin-left: var(--sidebar-width); min-height:100vh; }
    .page-head { background:#fff; border-bottom:1px solid var(--sb-border); min-height:var(--header-height); position:sticky; top:0; z-index:1030; }
    .avatar-sm { width:34px; height:34px; border-radius:50%; background:var(--brand-tint); color:var(--brand); display:inline-flex; align-items:center; justify-content:center; font-weight:600; }

    /* Modal fix (design-tokens-free layout, but keep parity if tokens load elsewhere) */
    .modal.show { display:block; }

    .sidebar-backdrop { display:none; }
    @media (max-width: 991px) {
      .sidebar { transform: translateX(-100%); transition: transform .2s; }
      .sidebar.show { transform:none; }
      .content { margin-left:0 !important; }
      .sidebar-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:1039; }
    }
  </style>
</head>
<body>
  @php
    $staffUser = auth()->user();
    $role = $staffUser?->getRoleNames()->first() ?? 'Staff';
  @endphp

  <aside class="sidebar" id="staffSidebar">
    <div class="sidebar-header">
      <a href="{{ route('staff.dashboard') }}" class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-mortarboard-fill"></i></span>
        <span>Staff Portal</span>
      </a>
      <button class="btn btn-sm ms-auto d-lg-none" id="staffSidebarClose"><i class="bi bi-x-lg"></i></button>
    </div>
    <nav class="sidebar-nav">
      <a href="{{ route('staff.dashboard') }}" class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard</a>

      <div class="section-label">Teaching</div>
      <a href="#" class="nav-link disabled"><i class="bi bi-calendar-check"></i> Attendance <span class="soon-badge">soon</span></a>
      <a href="#" class="nav-link disabled"><i class="bi bi-journal-text"></i> Marks &amp; Results <span class="soon-badge">soon</span></a>
      <a href="#" class="nav-link disabled"><i class="bi bi-calendar3-week"></i> Class Routine <span class="soon-badge">soon</span></a>

      <div class="section-label">General</div>
      <a href="{{ route('staff.notices') }}" class="nav-link {{ request()->routeIs('staff.notices') ? 'active' : '' }}"><i class="bi bi-megaphone"></i> Notices</a>
      <a href="#" class="nav-link disabled"><i class="bi bi-chat-left-text"></i> Messages <span class="soon-badge">soon</span></a>
      <a href="{{ route('staff.profile') }}" class="nav-link {{ request()->routeIs('staff.profile') ? 'active' : '' }}"><i class="bi bi-person-badge"></i> My Profile</a>
    </nav>
    <div class="sidebar-footer">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-box-arrow-right me-1"></i> Sign out</button>
      </form>
    </div>
  </aside>
  <div class="sidebar-backdrop" id="staffBackdrop"></div>

  <div class="content">
    <header class="page-head">
      <div class="d-flex align-items-center h-100 px-3 px-lg-4" style="min-height:var(--header-height)">
        <button class="btn btn-sm d-lg-none me-2" id="staffSidebarToggle"><i class="bi bi-list fs-5"></i></button>
        <div class="fw-semibold">@yield('heading', 'Dashboard')</div>
        <div class="ms-auto d-flex align-items-center gap-2">
          <div class="text-end d-none d-sm-block">
            <div class="fw-medium small">{{ $staffUser?->name }}</div>
            <div class="text-muted" style="font-size:.72rem; text-transform:capitalize;">{{ $role }}</div>
          </div>
          <span class="avatar-sm">{{ strtoupper(substr($staffUser?->name ?? 'S', 0, 1)) }}</span>
        </div>
      </div>
    </header>

    <main class="p-3 p-lg-4" style="max-width:1200px; margin:0 auto;">
      @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
      @endif
      @yield('content')
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      var sb = document.getElementById('staffSidebar');
      var bd = document.getElementById('staffBackdrop');
      function open(v){ sb.classList.toggle('show', v); bd.style.display = v ? 'block' : 'none'; }
      document.getElementById('staffSidebarToggle')?.addEventListener('click', function(){ open(true); });
      document.getElementById('staffSidebarClose')?.addEventListener('click', function(){ open(false); });
      bd?.addEventListener('click', function(){ open(false); });
    })();
  </script>
  @stack('scripts')
</body>
</html>
