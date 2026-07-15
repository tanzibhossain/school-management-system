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
  <link href="{{ asset('css/admin-design-tokens.css') }}" rel="stylesheet">
  <style>
    /* Legacy styles - being migrated to design tokens */
    :root { --sidebar-width: var(--sidebar-width); }
    body { background: var(--color-bg); }
    .sidebar { width: var(--sidebar-width); min-height: 100vh; overflow-y: auto; }
    .sidebar .brand { font-weight: 700; color: var(--color-primary); }
    .sidebar .nav-section { font-size: var(--text-xs); letter-spacing: var(--tracking-wider); }
    .sidebar .nav-link { color: var(--color-text-muted); border-radius: var(--radius); padding: var(--space-2) var(--space-3); font-size: var(--text-sm); display: flex; align-items: center; gap: var(--space-2); }
    .sidebar .nav-link:hover { background: var(--color-bg-hover); }
    .sidebar .nav-link.active { background: var(--color-primary-light); color: var(--color-primary); font-weight: 600; }
    .sidebar .nav-link i { width: 1.25rem; text-align: center; }
    .content { margin-left: var(--sidebar-width); }
    .page-head { background: var(--color-surface); }
    .card { border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); border-radius: var(--radius-card); }
    .card-header { background: var(--color-bg-subtle); font-weight: 600; }
    table.dataTable thead th { white-space: nowrap; }
    @media (max-width: 991px) { .sidebar { position: fixed; z-index: var(--z-fixed); transform: translateX(-100%); transition: transform var(--transition-base); } .sidebar.show { transform: none; } .content { margin-left: 0; } }
  </style>
</head>
<body>
  @php
    $u = auth()->user();
    $enabledModules = collect(app(\App\Modules\School\Services\ModuleSettingService::class)
        ->allForSchool(app('current_school_id')))
        ->filter(fn ($m) => $m['is_enabled'])->pluck('module')->all();
    $isAdmin = $u->hasRole('admin');
    $canFinance = $isAdmin || $u->hasRole('accountant');

    // User data for header/sidebar
    $headerUser = [
        'name' => $u->name,
        'email' => $u->email,
        'role' => $u->getRoleNames()->first() ?? 'User',
        'avatar' => null,
        'menu' => '<li><a class="dropdown-item" href="' . route('admin.users.index') . '"><i class="bi bi-person me-2"></i> Users</a></li>
                 <li><hr class="dropdown-divider"></li>
                 <li><form method="POST" action="' . route('logout') . '">@csrf <button type="submit" class="dropdown-item text-danger w-100 text-start"><i class="bi bi-box-arrow-right me-2"></i> Sign out</button></form></li>',
    ];

    // Quick actions for mobile header
    $quickActions = [
        ['label' => 'New Student', 'icon' => 'bi-person-plus', 'url' => route('admin.students.create')],
        ['label' => 'New Staff', 'icon' => 'bi-person-badge-plus', 'url' => route('admin.staff.store')],
        ['label' => 'New Admission', 'icon' => 'bi-clipboard-check', 'url' => route('admin.admissions.index')],
    ];
  @endphp

  <x-sidebar
    :collapsed="false"
    :nav-items="[]"
    :brand="['icon' => 'bi-mortarboard-fill', 'text' => 'School Admin', 'href' => route('admin.dashboard')]"
    :user="$headerUser"
    class="bg-white border-end position-fixed"
  />

  <div class="content">
    <x-header
        :user="$headerUser"
        :notifications="[]"
        :searchable="true"
        :quickActions="$quickActions"
    />

    <main class="p-3 p-lg-4" style="padding: var(--content-padding); max-width: var(--content-max); margin: 0 auto;">
      @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle"></i> {{ session('status') }}<button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
      @endif
      @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }}<button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle"></i> Please fix the following:</div>
          <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
          <button class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
  <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
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