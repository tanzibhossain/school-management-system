<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Platform') · Super Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <style>
    :root { --sb-w: 248px; }
    body { background:#f5f7fb; }
    .sidebar { width:var(--sb-w); min-height:100vh; overflow-y:auto; }
    .sidebar .brand { font-weight:700; color:#7c3aed; }
    .sidebar .nav-section { font-size:.7rem; letter-spacing:.06em; }
    .sidebar .nav-link { color:#3f4a5a; border-radius:.5rem; padding:.4rem .75rem; font-size:.925rem; display:flex; align-items:center; gap:.55rem; }
    .sidebar .nav-link:hover { background:#f2eefc; }
    .sidebar .nav-link.active { background:#efe7ff; color:#7c3aed; font-weight:600; }
    .sidebar .nav-link i { width:1.1rem; text-align:center; }
    .content { margin-left:var(--sb-w); }
    .page-head { background:#fff; }
    .card { border:0; box-shadow:0 1px 2px rgba(16,24,40,.06),0 1px 3px rgba(16,24,40,.04); }
    .card-header { background:#fff; font-weight:600; }
    table.dataTable thead th { white-space:nowrap; }
    @media (max-width: 991px){ .sidebar{ position:fixed; z-index:1040; transform:translateX(-100%); transition:.2s; } .sidebar.show{ transform:none; } .content{ margin-left:0; } }
  </style>
</head>
<body>
  @php $u = auth()->user(); @endphp
  <nav class="sidebar bg-white border-end position-fixed p-3">
    <div class="brand fs-5 mb-3 px-2 d-flex align-items-center gap-2"><i class="bi bi-shield-lock-fill"></i> Super Admin</div>
    <ul class="nav nav-pills flex-column gap-1">
      <li class="nav-section text-uppercase text-muted px-2 pt-2 pb-1">Platform</li>
      <li><a class="nav-link {{ request()->routeIs('platform.schools.*') ? 'active' : '' }}" href="{{ route('platform.schools.index') }}"><i class="bi bi-buildings"></i> Schools</a></li>
      <li><a class="nav-link {{ request()->routeIs('platform.plans.*') ? 'active' : '' }}" href="{{ route('platform.plans.index') }}"><i class="bi bi-box-seam"></i> Plans</a></li>
      <li><a class="nav-link {{ request()->routeIs('platform.signups.*') ? 'active' : '' }}" href="{{ route('platform.signups.index') }}"><i class="bi bi-hourglass-split"></i> Pending signups</a></li>

      @if ($u->hasRole('admin'))
        <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">School</li>
        <li><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-arrow-left-circle"></i> School admin</a></li>
      @endif
    </ul>
  </nav>

  <div class="content">
    <nav class="navbar page-head border-bottom px-3 px-lg-4 py-2">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="document.querySelector('.sidebar').classList.toggle('show')"><i class="bi bi-list"></i></button>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-muted small"><i class="bi bi-person-circle"></i> {{ $u->name }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Sign out</button></form>
      </div>
    </nav>

    <main class="p-3 p-lg-4">
      @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('status') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
      @endif
      @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
          <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle"></i> Please fix the following:</div>
          <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
          <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      @endif
      @yield('content')
    </main>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(function () {
      $('table.js-dt').each(function () {
        var $t = $(this); var noSort = [];
        $t.find('thead th').each(function (i) { if ($(this).data('orderable') === false) noSort.push(i); });
        $t.DataTable({ pageLength: 25, order: [], columnDefs: [{ orderable: false, targets: noSort }] });
      });
    });
    @if (session('open_modal'))
      var m = document.getElementById(@json(session('open_modal')));
      if (m) new bootstrap.Modal(m).show();
    @endif
  </script>
  @stack('scripts')
</body>
</html>
