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
  @endphp
  <nav class="sidebar bg-white border-end position-fixed p-3" style="width: var(--sidebar-width);" aria-label="Main navigation">
    <div class="brand fs-5 mb-4 px-2 d-flex align-items-center gap-2">
      <i class="bi bi-mortarboard-fill" style="color: var(--color-primary);"></i>
      <span class="fw-bold text-slate-900">School Admin</span>
    </div>
    <ul class="nav nav-pills flex-column gap-1" role="navigation" aria-label="Main navigation">
      <li><a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>

      @if ($isAdmin)
      <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">Setup</li>
      <li><a class="nav-link {{ request()->routeIs('admin.school.*') ? 'active' : '' }}" href="{{ route('admin.school.edit') }}"><i class="bi bi-building-gear"></i> School settings</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.modules.*') ? 'active' : '' }}" href="{{ route('admin.modules.index') }}"><i class="bi bi-toggles"></i> Modules</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" href="{{ route('admin.pages.index') }}"><i class="bi bi-window"></i> Website pages</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.academic-years.*') ? 'active' : '' }}" href="{{ route('admin.academic-years.index') }}"><i class="bi bi-calendar3"></i> Academic years</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.classes.*') || request()->routeIs('admin.sections.*') ? 'active' : '' }}" href="{{ route('admin.classes.index') }}"><i class="bi bi-diagram-3"></i> Classes & sections</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}" href="{{ route('admin.subjects.index') }}"><i class="bi bi-book"></i> Subjects</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.groups.*') ? 'active' : '' }}" href="{{ route('admin.groups.index') }}"><i class="bi bi-people"></i> Groups</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.versions.*') ? 'active' : '' }}" href="{{ route('admin.versions.index') }}"><i class="bi bi-translate"></i> Versions</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.shifts.*') ? 'active' : '' }}" href="{{ route('admin.shifts.index') }}"><i class="bi bi-clock-history"></i> Shifts</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.routine.*') || request()->routeIs('admin.routine-setup.*') ? 'active' : '' }}" href="{{ route('admin.routine.index') }}"><i class="bi bi-calendar3-week"></i> Class routine</a></li>

      <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">People</li>
      <li><a class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}" href="{{ route('admin.students.index') }}"><i class="bi bi-people-fill"></i> Students</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}" href="{{ route('admin.staff.index') }}"><i class="bi bi-person-badge"></i> Staff</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.designations.*') ? 'active' : '' }}" href="{{ route('admin.designations.index') }}"><i class="bi bi-award"></i> Designations</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}"><i class="bi bi-building"></i> Departments</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.admissions.*') ? 'active' : '' }}" href="{{ route('admin.admissions.index') }}"><i class="bi bi-clipboard-check"></i> Admissions</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.data-import.*') ? 'active' : '' }}" href="{{ route('admin.data-import.index') }}"><i class="bi bi-upload"></i> Data import</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-person-gear"></i> Users & roles</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.testimonials.*') || request()->routeIs('admin.admit-cards.*') || request()->routeIs('admin.cert-templates.*') || request()->routeIs('admin.id-cards.*') || request()->routeIs('admin.id-card-templates.*') ? 'active' : '' }}" href="{{ route('admin.testimonials.index') }}"><i class="bi bi-file-earmark-medical"></i> Certificates & IDs</a></li>
      @endif

      @if ($canFinance)
      <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">Finance</li>
      <li><a class="nav-link {{ request()->routeIs('admin.fee-categories.*') ? 'active' : '' }}" href="{{ route('admin.fee-categories.index') }}"><i class="bi bi-tags"></i> Fee categories</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.fee-items.*') ? 'active' : '' }}" href="{{ route('admin.fee-items.index') }}"><i class="bi bi-cash-stack"></i> Fee items</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.fee-discounts.*') ? 'active' : '' }}" href="{{ route('admin.fee-discounts.index') }}"><i class="bi bi-percent"></i> Discounts</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><i class="bi bi-receipt"></i> Invoices</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}" href="{{ route('admin.payments.index') }}"><i class="bi bi-credit-card"></i> Payments</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}" href="{{ route('admin.refunds.index') }}"><i class="bi bi-arrow-return-left"></i> Refunds</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.student-credit.*') ? 'active' : '' }}" href="{{ route('admin.student-credit.index') }}"><i class="bi bi-wallet2"></i> Student credit</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.payment-config.*') ? 'active' : '' }}" href="{{ route('admin.payment-config.edit') }}"><i class="bi bi-gear"></i> Payment config</a></li>
      @endif

      @if ($isAdmin)
      <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">Academics</li>
      <li><a class="nav-link {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}" href="{{ route('admin.attendance.index') }}"><i class="bi bi-calendar-check"></i> Attendance</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.exam-types.*') ? 'active' : '' }}" href="{{ route('admin.exam-types.index') }}"><i class="bi bi-card-list"></i> Exam types</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.exams.*') || request()->routeIs('admin.exam-marks.*') ? 'active' : '' }}" href="{{ route('admin.exams.index') }}"><i class="bi bi-journal-text"></i> Exams</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.mark-settings.*') ? 'active' : '' }}" href="{{ route('admin.mark-settings.index') }}"><i class="bi bi-sliders"></i> Mark settings</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.exam-halls.*') ? 'active' : '' }}" href="{{ route('admin.exam-halls.index') }}"><i class="bi bi-grid-3x3"></i> Exam halls</a></li>

      <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">Comms</li>
      <li><a class="nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}" href="{{ route('admin.announcements.index') }}"><i class="bi bi-megaphone"></i> Announcements</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.sms.*') ? 'active' : '' }}" href="{{ route('admin.sms.index') }}"><i class="bi bi-chat-dots"></i> SMS</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}" href="{{ route('admin.messages.index') }}"><i class="bi bi-chat-left-text"></i> Messages</a></li>

      <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">HR</li>
      <li><a class="nav-link {{ request()->routeIs('admin.leave-types.*') ? 'active' : '' }}" href="{{ route('admin.leave-types.index') }}"><i class="bi bi-card-checklist"></i> Leave types</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.student-leave.*') ? 'active' : '' }}" href="{{ route('admin.student-leave.index') }}"><i class="bi bi-person-vcard"></i> Student leave</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.staff-leave.*') ? 'active' : '' }}" href="{{ route('admin.staff-leave.index') }}"><i class="bi bi-person-workspace"></i> Staff leave</a></li>
      <li><a class="nav-link {{ request()->routeIs('admin.staff-loans.*') ? 'active' : '' }}" href="{{ route('admin.staff-loans.index') }}"><i class="bi bi-cash-stack"></i> Staff loans</a></li>
      @endif

      @if ($canFinance)
      <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">Reports</li>
      <li><a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.fee-collection') }}"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a></li>
      @endif

      @if ($isAdmin && $enabledModules)
        <li class="nav-section text-uppercase text-muted px-2 pt-3 pb-1">Optional</li>
        @if (in_array('library', $enabledModules))
          <li><a class="nav-link {{ request()->routeIs('admin.library.*') ? 'active' : '' }}" href="{{ route('admin.library.books.index') }}"><i class="bi bi-book-half"></i> Library</a></li>
        @endif
        @if (in_array('transport', $enabledModules))
          <li><a class="nav-link {{ request()->routeIs('admin.transport.*') ? 'active' : '' }}" href="{{ route('admin.transport.routes.index') }}"><i class="bi bi-bus-front"></i> Transport</a></li>
        @endif
        @if (in_array('payroll', $enabledModules))
          <li><a class="nav-link {{ request()->routeIs('admin.payroll.*') ? 'active' : '' }}" href="{{ route('admin.payroll.runs.index') }}"><i class="bi bi-cash-coin"></i> Payroll</a></li>
        @endif
        @if (in_array('lms', $enabledModules))
          <li><a class="nav-link {{ request()->routeIs('admin.lms.*') ? 'active' : '' }}" href="{{ route('admin.lms.courses.index') }}"><i class="bi bi-easel"></i> LMS</a></li>
        @endif
      @endif
    </ul>
  </nav>

  <div class="content">
    <nav class="navbar page-head border-bottom px-3 px-lg-4 py-2" style="background: var(--color-surface); height: var(--header-height);">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="document.querySelector('.sidebar').classList.toggle('show')" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-muted small"><i class="bi bi-person-circle"></i> {{ $u->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn btn-sm btn-outline-secondary" type="submit">Sign out</button>
        </form>
      </div>
    </nav>

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