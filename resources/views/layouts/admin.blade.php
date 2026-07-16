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
      --sidebar-collapsed-width: 72px;
      --header-height: 64px;
      --content-max: 1280px;
      --content-padding: 1.5rem;

      /* Modern color palette */
      --sb-bg: #ffffff;
      --sb-border: #e8ecf1;
      --sb-primary: #1d4ed8;
      --sb-primary-hover: #1e40af;
      --sb-primary-light: #eff6ff;
      --sb-text: #374151;
      --sb-text-muted: #6b7280;
      --sb-hover: #f8fafc;
      --sb-active-bg: #eff6ff;
      --sb-active-border: #bfdbfe;
      --sb-section-text: #9ca3af;
      --sb-scrollbar: #d1d5db;
      --sb-scrollbar-hover: #9ca3af;
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
      :root {
        --sb-bg: #1e293b;
        --sb-border: #334155;
        --sb-primary: #3b82f6;
        --sb-primary-hover: #60a5fa;
        --sb-primary-light: #1e3a5f;
        --sb-text: #f1f5f9;
        --sb-text-muted: #94a3b8;
        --sb-hover: #334155;
        --sb-active-bg: #1e3a5f;
        --sb-active-border: #3b82f6;
        --sb-section-text: #64748b;
        --sb-scrollbar: #475569;
        --sb-scrollbar-hover: #64748b;
      }
    }

    body { background: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; }

    /* ── Sidebar shell ── */
    .sidebar {
      width: var(--sidebar-width);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: var(--sb-bg);
      border-right: 1px solid var(--sb-border);
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1040;
      transition: width 0.2s ease, transform 0.2s ease;
    }

    /* Header/brand area */
    .sidebar-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: var(--header-height);
      padding: 0 1rem;
      border-bottom: 1px solid var(--sb-border);
      flex-shrink: 0;
    }

    .sidebar-brand {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      color: var(--sb-primary);
      text-decoration: none;
      font-weight: 700;
      font-size: 1.1rem;
      transition: opacity 0.15s ease;
      white-space: nowrap;
      overflow: hidden;
    }

    .sidebar-brand:hover {
      opacity: 0.85;
    }

    .brand-icon {
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--sb-primary-light);
      border-radius: 10px;
      color: var(--sb-primary);
      font-size: 1.25rem;
      flex-shrink: 0;
    }

    .brand-text {
      transition: opacity 0.15s ease, width 0.15s ease;
    }

    /* Close button (mobile) */
    .sidebar-close {
      border: 0;
      background: transparent;
      color: var(--sb-text-muted);
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s ease;
      flex-shrink: 0;
    }

    .sidebar-close:hover {
      background: var(--sb-hover);
      color: var(--sb-primary);
    }

    /* Navigation area - takes remaining space */
    .sidebar-nav {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 0.75rem 0.5rem 1.5rem;
      min-height: 0; /* Critical for flex child scrolling */
    }

    /* Custom scrollbar */
    .sidebar-nav::-webkit-scrollbar { width: 6px; }
    .sidebar-nav::-webkit-scrollbar-thumb {
      background: var(--sb-scrollbar);
      border-radius: 3px;
    }
    .sidebar-nav::-webkit-scrollbar-thumb:hover { background: var(--sb-scrollbar-hover); }
    .sidebar-nav::-webkit-scrollbar-track { background: transparent; }

    /* Section groups (accordion) */
    .nav-group {
      margin-top: 0.5rem;
    }

    /* Flat group (Dashboard) */
    .nav-group--flat {
      padding: 0.25rem 0.25rem 0.5rem;
    }

    /* Group toggle button */
    .nav-group-toggle {
      width: calc(100% - 0.5rem);
      margin: 0.125rem 0.25rem;
      padding: 0.5rem 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.625rem;
      border: 0;
      background: transparent;
      cursor: pointer;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.02em;
      text-transform: uppercase;
      color: var(--sb-section-text);
      transition: all 0.15s ease;
    }

    .nav-group-toggle:hover {
      background: var(--sb-hover);
      color: var(--sb-text);
    }

    .nav-group-icon {
      width: 1.25rem;
      font-size: 1rem;
      text-align: center;
      flex-shrink: 0;
      color: var(--sb-section-text);
      transition: color 0.15s ease;
    }

    .nav-group-toggle:hover .nav-group-icon {
      color: var(--sb-primary);
    }

    /* Active parent section */
    .nav-group-toggle.has-active {
      background: var(--sb-active-bg);
      color: var(--sb-primary);
    }

    .nav-group-toggle.has-active .nav-group-icon {
      color: var(--sb-primary);
    }

    .nav-group-caret {
      font-size: 0.625rem;
      transition: transform 0.2s ease;
      flex-shrink: 0;
      color: var(--sb-text-muted);
    }

    .nav-group--open .nav-group-caret {
      transform: rotate(180deg);
    }

    /* Group items container */
    .nav-group-items {
      overflow: hidden;
      max-height: 0;
      opacity: 0;
      transition: max-height 0.25s ease, opacity 0.2s ease;
      padding: 0.125rem 0.25rem 0.25rem;
    }

    .nav-group--open .nav-group-items {
      max-height: 500px; /* Sufficient for all items */
      opacity: 1;
    }

    /* Navigation links */
    .nav-link {
      color: var(--sb-text);
      border-radius: 8px;
      margin: 0.125rem 0.25rem;
      padding: 0.5rem 0.75rem;
      font-size: 0.875rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.625rem;
      white-space: nowrap;
      text-transform: capitalize;
      transition: all 0.15s ease;
      position: relative;
    }

    .nav-link:hover {
      background: var(--sb-hover);
      color: var(--sb-primary);
    }

    .nav-link.active {
      background: var(--sb-active-bg);
      color: var(--sb-primary);
      font-weight: 600;
    }

    .nav-link.active::before {
      content: '';
      position: absolute;
      left: 0.25rem;
      top: 50%;
      transform: translateY(-50%);
      width: 3px;
      height: 1.25rem;
      background: var(--sb-primary);
      border-radius: 0 2px 2px 0;
    }

    .nav-icon {
      width: 1.25rem;
      font-size: 1rem;
      text-align: center;
      flex-shrink: 0;
      color: var(--sb-text-muted);
      transition: color 0.15s ease;
    }

    .nav-link:hover .nav-icon,
    .nav-link.active .nav-icon {
      color: var(--sb-primary);
    }

    /* Footer */
    .sidebar-footer {
      border-top: 1px solid var(--sb-border);
      padding: 1rem;
      flex-shrink: 0;
    }

    /* ── Content offset ── */
    .content {
      margin-left: var(--sidebar-width);
      min-height: 100vh;
      transition: margin-left 0.2s ease;
    }

    .card { border: 1px solid #eef0f4; box-shadow: 0 1px 2px rgba(16,24,40,.05); border-radius: 12px; }
    .card-header { background: #fff; font-weight: 600; }
    table.dataTable thead th { white-space: nowrap; }

    /* ── Mobile: off-canvas ── */
    .sidebar-backdrop { display: none; }
    @media (max-width: 991px) {
      .sidebar { transform: translateX(-100%); transition: transform 0.2s ease; }
      .sidebar.show { transform: none; }
      .content { margin-left: 0 !important; }
      .sidebar-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,.45);
        z-index: 1039;
        display: block;
      }
    }

    /* ── Laptop/small desktop: limit sidebar height to viewport ── */
    @media (max-height: 850px) {
      .sidebar-nav {
        /* Ensure scrolling works on smaller laptop screens */
        max-height: calc(100vh - var(--header-height) - 80px);
      }
    }

    /* Reduced motion */
    @media (prefers-reduced-motion: reduce) {
      .sidebar,
      .nav-group-items,
      .nav-link,
      .nav-group-toggle,
      .brand-text {
        transition: none !important;
      }
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
        ['label' => 'New Staff', 'icon' => 'bi-person-badge', 'url' => route('admin.staff.index')],
        ['label' => 'New Admission', 'icon' => 'bi-clipboard-check', 'url' => route('admin.admissions.index')],
    ];
  @endphp

  <x-sidebar
    :collapsed="false"
    :is-admin="$isAdmin"
    :can-finance="$canFinance"
    :enabled-modules="$enabledModules"
    :brand="['icon' => 'bi-mortarboard-fill', 'text' => 'School Admin', 'href' => route('admin.dashboard')]"
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