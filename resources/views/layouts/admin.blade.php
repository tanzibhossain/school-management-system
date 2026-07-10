<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Admin') · School Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
  <style>
    :root { --sb-w: 248px; }
    body { background:#f5f7fb; }
    .sidebar { width:var(--sb-w); min-height:100vh; overflow-y:auto; }
    .sidebar .brand { font-weight:700; color:#1d4ed8; }
    .sidebar .nav-section { font-size:.7rem; letter-spacing:.06em; }
    .sidebar .nav-link { color:#3f4a5a; border-radius:.5rem; padding:.4rem .75rem; font-size:.925rem; display:flex; align-items:center; gap:.55rem; }
    .sidebar .nav-link:hover { background:#eef2f9; }
    .sidebar .nav-link.active { background:#e7f0ff; color:#1d4ed8; font-weight:600; }
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
    <div class="brand fs-5 mb-3 px-2 d-flex align-items-center gap-2"><i class="bi bi-mortarboard-fill"></i> School Admin</div>
    <ul class="nav nav-pills flex-column gap-1">
      <li><a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>

      <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">Setup</li>
      <li><a class="nav-link {{ request()->routeIs('admin.school.*') ? 'active' : '' }}" href="{{ route('admin.school.edit') }}"><i class="bi bi-building-gear"></i> School settings</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.modules.*') ? 'active' : '' }}" href="{{ route('admin.modules.index') }}"><i class="bi bi-toggles"></i> Modules</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.academic-years.*') ? 'active' : '' }}" href="{{ route('admin.academic-years.index') }}"><i class="bi bi-calendar3"></i> Academic years</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.classes.*') || request()->routeIs('admin.sections.*') ? 'active' : '' }}" href="{{ route('admin.classes.index') }}"><i class="bi bi-diagram-3"></i> Classes &amp; sections</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}" href="{{ route('admin.subjects.index') }}"><i class="bi bi-book"></i> Subjects</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.groups.*') ? 'active' : '' }}" href="{{ route('admin.groups.index') }}"><i class="bi bi-people"></i> Groups</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.versions.*') ? 'active' : '' }}" href="{{ route('admin.versions.index') }}"><i class="bi bi-translate"></i> Versions</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.shifts.*') ? 'active' : '' }}" href="{{ route('admin.shifts.index') }}"><i class="bi bi-clock-history"></i> Shifts</a></li>

      <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">People</li>
      <li><a class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}" href="{{ route('admin.students.index') }}"><i class="bi bi-people-fill"></i> Students</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}" href="{{ route('admin.staff.index') }}"><i class="bi bi-person-badge"></i> Staff</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.designations.*') ? 'active' : '' }}" href="{{ route('admin.designations.index') }}"><i class="bi bi-award"></i> Designations</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}"><i class="bi bi-building"></i> Departments</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-person-gear"></i> Users &amp; roles</a></li>
    </ul>
  </nav>

  <div class="content">
    <nav class="navbar page-head border-bottom px-3 px-lg-4 py-2">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="document.querySelector('.sidebar').classList.toggle('show')"><i class="bi bi-list"></i></button>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-muted small"><i class="bi bi-person-circle"></i> {{ $u->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn btn-sm btn-outline-secondary">Sign out</button>
        </form>
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
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
  <script>
    // Auto-init any table with .js-dt as a DataTable; opt out per-column with data-orderable="false".
    $(function () {
      $('table.js-dt').each(function () {
        var $t = $(this); var noSort = [];
        $t.find('thead th').each(function (i) { if ($(this).data('orderable') === false) noSort.push(i); });
        $t.DataTable({ pageLength: 25, order: [], columnDefs: [{ orderable: false, targets: noSort }] });
      });
      document.querySelectorAll('.js-select').forEach(function (el) { new TomSelect(el, { create: false }); });
    });
    // Re-open a modal after a validation redirect (?open=modalId in session)
    @if (session('open_modal'))
      var m = document.getElementById(@json(session('open_modal')));
      if (m) new bootstrap.Modal(m).show();
    @endif
  </script>
  @stack('scripts')
</body>
</html>
