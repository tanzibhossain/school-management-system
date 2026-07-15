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
  {{-- The design-system components (sidebar, header, command palette) use Tailwind
       utility classes. Load Tailwind with Preflight OFF so it supplies the utilities
       without resetting Bootstrap. (Play CDN — swap for a compiled build in production.) --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config = { corePlugins: { preflight: false } };</script>
  <style>
    :root {
      --sidebar-width: 264px;
      --sidebar-collapsed: 76px;
      --sb-bg: #ffffff;
      --sb-border: #e9edf3;
      --sb-primary: var(--color-primary, #1d4ed8);
      --sb-text: #414b5a;
      --sb-muted: #8a94a6;
      --sb-hover: #f3f6fb;
      --sb-active-bg: color-mix(in srgb, var(--sb-primary) 12%, #ffffff);
    }
    body { background: #f5f7fb; }

    /* ── Sidebar shell ─────────────────────────────────────────── */
    .sidebar {
      width: var(--sidebar-width); min-height: 100vh;
      display: flex; flex-direction: column;
      background: var(--sb-bg); border-right: 1px solid var(--sb-border);
      position: fixed; top: 0; left: 0; z-index: 1040;
      overflow: hidden; transition: width .22s cubic-bezier(.4,0,.2,1);
    }
    .sidebar.collapsed { width: var(--sidebar-collapsed); }

    /* Brand + collapse toggle */
    .sidebar-brand { min-height: 60px; border-bottom: 1px solid var(--sb-border); }
    .sidebar.collapsed .sidebar-brand { justify-content: center; padding-left: .5rem; padding-right: .5rem; }
    .sidebar.collapsed .sidebar-brand a, .sidebar.collapsed .sidebar-brand > i { display: none !important; }
    .sidebar-toggle {
      border: 0; background: transparent; color: var(--sb-muted);
      width: 34px; height: 34px; border-radius: 9px; display: inline-flex;
      align-items: center; justify-content: center; transition: .15s;
    }
    .sidebar-toggle:hover { background: var(--sb-hover); color: var(--sb-primary); }

    /* Nav */
    .sidebar-nav { flex: 1; overflow-y: auto; overflow-x: hidden; padding: .35rem 0 1rem; }
    .sidebar-nav::-webkit-scrollbar { width: 6px; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: #d9dee7; border-radius: 3px; }
    .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    .sidebar .nav-section { margin-top: .5rem; }
    .sidebar .nav-section span { font-size: .68rem; letter-spacing: .08em; color: var(--sb-muted); font-weight: 700; }
    .sidebar.collapsed .nav-section { height: 0; margin: .55rem .9rem; padding: 0 !important; border-top: 1px solid var(--sb-border); }
    .sidebar.collapsed .nav-section span { display: none; }
    .sidebar .nav-link {
      color: var(--sb-text); border-radius: 10px; margin: 1px .5rem; padding: .55rem .7rem;
      font-size: .92rem; font-weight: 500; display: flex; align-items: center; gap: .7rem;
      white-space: nowrap; transition: background .15s, color .15s;
    }
    .sidebar .nav-link:hover { background: var(--sb-hover); color: var(--sb-primary); }
    .sidebar .nav-link.active { background: var(--sb-active-bg); color: var(--sb-primary); font-weight: 600; }
    .sidebar .nav-link .nav-icon { width: 1.35rem; font-size: 1.05rem; text-align: center; flex-shrink: 0; }
    .sidebar.collapsed .nav-link { justify-content: center; padding: .6rem; margin: 2px .65rem; }
    .sidebar.collapsed .nav-label, .sidebar.collapsed .nav-link .badge { display: none !important; }

    /* Footer */
    .sidebar-footer { border-top: 1px solid var(--sb-border); }
    .sidebar.collapsed .sidebar-footer .flex-grow-1, .sidebar.collapsed .sidebar-footer .dropdown { display: none !important; }

    /* ── Content offset (follows collapse via :has) ───────────── */
    .content { margin-left: var(--sidebar-width); transition: margin-left .22s cubic-bezier(.4,0,.2,1); }
    body:has(.sidebar.collapsed) .content { margin-left: var(--sidebar-collapsed); }

    .card { border: 1px solid #eef0f4; box-shadow: 0 1px 2px rgba(16,24,40,.05); border-radius: 12px; }
    .card-header { background: #fff; font-weight: 600; }
    table.dataTable thead th { white-space: nowrap; }

    /* ── Mobile: off-canvas ───────────────────────────────────── */
    .sidebar-backdrop { display: none; }
    @media (max-width: 991px) {
      .sidebar { transform: translateX(-100%); width: var(--sidebar-width) !important; transition: transform .2s; }
      .sidebar.show { transform: none; }
      .content { margin-left: 0 !important; }
      body:has(.sidebar.collapsed) .content { margin-left: 0 !important; }
      .sidebar-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.45); z-index: 1039; }
    }
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
    :is-admin="$isAdmin"
    :can-finance="$canFinance"
    :enabled-modules="$enabledModules"
    :brand="['icon' => 'bi-mortarboard-fill', 'text' => 'School Admin', 'href' => route('admin.dashboard')]"
    :user="$headerUser"
    class="bg-white border-end position-fixed"
  />

  <x-command-palette :enabledModules="$enabledModules" />

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