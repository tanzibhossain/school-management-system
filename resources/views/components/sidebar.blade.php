{{-- Modern Sidebar Component --}}
@props([
    'collapsed' => false,
    'isAdmin' => false,
    'canFinance' => false,
    'enabledModules' => [],
    'currentRoute' => null,
    'user' => null,
    'footer' => null,
    'class' => '',
])

@php
    $sidebarId = 'sidebar-' . uniqid();

    // Build navigation items
    $navItems = [];

    // Dashboard
    $navItems[] = [
        'label' => 'Dashboard',
        'icon' => 'bi-speedometer2',
        'href' => route('admin.dashboard'),
        'active' => request()->routeIs('admin.dashboard'),
    ];

    if ($isAdmin) {
        $navItems[] = ['section' => 'Setup'];
        $navItems[] = ['label' => 'School settings', 'icon' => 'bi-building-gear', 'href' => route('admin.school.edit'), 'active' => request()->routeIs('admin.school.*') || request()->routeIs('admin.modules.*')];
        $navItems[] = ['label' => 'Website pages', 'icon' => 'bi-window', 'href' => route('admin.pages.index'), 'active' => request()->routeIs('admin.pages.*')];
        $navItems[] = ['label' => 'Academic years', 'icon' => 'bi-calendar3', 'href' => route('admin.academic-years.index'), 'active' => request()->routeIs('admin.academic-years.*')];
        $navItems[] = ['label' => 'Classes & sections', 'icon' => 'bi-diagram-3', 'href' => route('admin.classes.index'), 'active' => request()->routeIs('admin.classes.*') || request()->routeIs('admin.sections.*')];
        $navItems[] = ['label' => 'Subjects', 'icon' => 'bi-book', 'href' => route('admin.subjects.index'), 'active' => request()->routeIs('admin.subjects.*')];
        $navItems[] = ['label' => 'Groups', 'icon' => 'bi-people', 'href' => route('admin.groups.index'), 'active' => request()->routeIs('admin.groups.*')];
        $navItems[] = ['label' => 'Versions', 'icon' => 'bi-translate', 'href' => route('admin.versions.index'), 'active' => request()->routeIs('admin.versions.*')];
        $navItems[] = ['label' => 'Shifts', 'icon' => 'bi-clock-history', 'href' => route('admin.shifts.index'), 'active' => request()->routeIs('admin.shifts.*')];
        $navItems[] = ['label' => 'Class routine', 'icon' => 'bi-calendar3-week', 'href' => route('admin.routine.index'), 'active' => request()->routeIs('admin.routine.*') || request()->routeIs('admin.routine-setup.*')];

        $navItems[] = ['section' => 'People'];
        $navItems[] = ['label' => 'Students', 'icon' => 'bi-people-fill', 'href' => route('admin.students.index'), 'active' => request()->routeIs('admin.students.*')];
        $navItems[] = ['label' => 'Staff', 'icon' => 'bi-person-badge', 'href' => route('admin.staff.index'), 'active' => request()->routeIs('admin.staff.*')];
        $navItems[] = ['label' => 'Designations', 'icon' => 'bi-award', 'href' => route('admin.designations.index'), 'active' => request()->routeIs('admin.designations.*')];
        $navItems[] = ['label' => 'Departments', 'icon' => 'bi-building', 'href' => route('admin.departments.index'), 'active' => request()->routeIs('admin.departments.*')];
        $navItems[] = ['label' => 'Admissions', 'icon' => 'bi-clipboard-check', 'href' => route('admin.admissions.index'), 'active' => request()->routeIs('admin.admissions.*')];
        $navItems[] = ['label' => 'Data import', 'icon' => 'bi-upload', 'href' => route('admin.data-import.index'), 'active' => request()->routeIs('admin.data-import.*')];
        $navItems[] = ['label' => 'Users & roles', 'icon' => 'bi-person-gear', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')];
        $navItems[] = ['label' => 'Certificates & IDs', 'icon' => 'bi-file-earmark-medical', 'href' => route('admin.testimonials.index'), 'active' => request()->routeIs('admin.testimonials.*') || request()->routeIs('admin.admit-cards.*') || request()->routeIs('admin.cert-templates.*') || request()->routeIs('admin.id-cards.*') || request()->routeIs('admin.id-card-templates.*')];
    }

    // Finance — admin OR accountant (routes are role:admin|accountant)
    if ($canFinance) {
            $navItems[] = ['section' => 'Finance'];
            $navItems[] = ['label' => 'Fee categories', 'icon' => 'bi-tags', 'href' => route('admin.fee-categories.index'), 'active' => request()->routeIs('admin.fee-categories.*')];
            $navItems[] = ['label' => 'Fee items', 'icon' => 'bi-cash-stack', 'href' => route('admin.fee-items.index'), 'active' => request()->routeIs('admin.fee-items.*')];
            $navItems[] = ['label' => 'Discounts', 'icon' => 'bi-percent', 'href' => route('admin.fee-discounts.index'), 'active' => request()->routeIs('admin.fee-discounts.*')];
            $navItems[] = ['label' => 'Invoices', 'icon' => 'bi-receipt', 'href' => route('admin.invoices.index'), 'active' => request()->routeIs('admin.invoices.*')];
            $navItems[] = ['label' => 'Payments', 'icon' => 'bi-credit-card', 'href' => route('admin.payments.index'), 'active' => request()->routeIs('admin.payments.*')];
            $navItems[] = ['label' => 'Refunds', 'icon' => 'bi-arrow-return-left', 'href' => route('admin.refunds.index'), 'active' => request()->routeIs('admin.refunds.*')];
            $navItems[] = ['label' => 'Student credit', 'icon' => 'bi-wallet2', 'href' => route('admin.student-credit.index'), 'active' => request()->routeIs('admin.student-credit.*')];
            $navItems[] = ['label' => 'Payment config', 'icon' => 'bi-gear', 'href' => route('admin.payment-config.edit'), 'active' => request()->routeIs('admin.payment-config.*')];
    }

    if ($isAdmin) {
        $navItems[] = ['section' => 'Academics'];
        $navItems[] = ['label' => 'Attendance', 'icon' => 'bi-calendar-check', 'href' => route('admin.attendance.index'), 'active' => request()->routeIs('admin.attendance.*')];
        $navItems[] = ['label' => 'Exam types', 'icon' => 'bi-card-list', 'href' => route('admin.exam-types.index'), 'active' => request()->routeIs('admin.exam-types.*')];
        $navItems[] = ['label' => 'Exams', 'icon' => 'bi-journal-text', 'href' => route('admin.exams.index'), 'active' => request()->routeIs('admin.exams.*') || request()->routeIs('admin.exam-marks.*')];
        $navItems[] = ['label' => 'Mark settings', 'icon' => 'bi-sliders', 'href' => route('admin.mark-settings.index'), 'active' => request()->routeIs('admin.mark-settings.*')];
        $navItems[] = ['label' => 'Exam halls', 'icon' => 'bi-grid-3x3', 'href' => route('admin.exam-halls.index'), 'active' => request()->routeIs('admin.exam-halls.*')];

        $navItems[] = ['section' => 'Comms'];
        $navItems[] = ['label' => 'Announcements', 'icon' => 'bi-megaphone', 'href' => route('admin.announcements.index'), 'active' => request()->routeIs('admin.announcements.*')];
        $navItems[] = ['label' => 'SMS', 'icon' => 'bi-chat-dots', 'href' => route('admin.sms.index'), 'active' => request()->routeIs('admin.sms.*')];
        $navItems[] = ['label' => 'Messages', 'icon' => 'bi-chat-left-text', 'href' => route('admin.messages.index'), 'active' => request()->routeIs('admin.messages.*')];
        $navItems[] = ['label' => 'Enquiries', 'icon' => 'bi-envelope-paper', 'href' => route('admin.enquiries.index'), 'active' => request()->routeIs('admin.enquiries.*')];

        $navItems[] = ['section' => 'HR'];
        $navItems[] = ['label' => 'Leave types', 'icon' => 'bi-card-checklist', 'href' => route('admin.leave-types.index'), 'active' => request()->routeIs('admin.leave-types.*')];
        $navItems[] = ['label' => 'Student leave', 'icon' => 'bi-person-vcard', 'href' => route('admin.student-leave.index'), 'active' => request()->routeIs('admin.student-leave.*')];
        $navItems[] = ['label' => 'Staff leave', 'icon' => 'bi-person-workspace', 'href' => route('admin.staff-leave.index'), 'active' => request()->routeIs('admin.staff-leave.*')];
        $navItems[] = ['label' => 'Staff loans', 'icon' => 'bi-cash-stack', 'href' => route('admin.staff-loans.index'), 'active' => request()->routeIs('admin.staff-loans.*')];
    }

    // Reports — admin OR accountant (routes are role:admin|accountant)
    if ($canFinance) {
        $navItems[] = ['section' => 'Reports'];
        $navItems[] = ['label' => 'Reports', 'icon' => 'bi-file-earmark-bar-graph', 'href' => route('admin.reports.fee-collection'), 'active' => request()->routeIs('admin.reports.*')];
    }

    if ($isAdmin && $enabledModules) {
        $navItems[] = ['section' => 'Optional'];
        if (in_array('library', $enabledModules)) {
            $navItems[] = ['label' => 'Library', 'icon' => 'bi-book-half', 'href' => route('admin.library.books.index'), 'active' => request()->routeIs('admin.library.*')];
        }
        if (in_array('transport', $enabledModules)) {
            $navItems[] = ['label' => 'Transport', 'icon' => 'bi-bus-front', 'href' => route('admin.transport.routes.index'), 'active' => request()->routeIs('admin.transport.*')];
        }
        if (in_array('payroll', $enabledModules)) {
            $navItems[] = ['label' => 'Payroll', 'icon' => 'bi-cash-coin', 'href' => route('admin.payroll.runs.index'), 'active' => request()->routeIs('admin.payroll.*')];
        }
        if (in_array('lms', $enabledModules)) {
            $navItems[] = ['label' => 'LMS', 'icon' => 'bi-easel', 'href' => route('admin.lms.courses.index'), 'active' => request()->routeIs('admin.lms.*')];
        }
    }

    $sidebarId = 'sidebar-' . uniqid();

    // Fold the flat nav list into collapsible groups. Items before the first
    // 'section' marker (i.e. Dashboard) live in an ungrouped, always-visible group.
    $navGroups = [];
    $current = ['section' => null, 'items' => []];
    foreach ($navItems as $it) {
        if (isset($it['section'])) {
            if (! empty($current['items'])) {
                $navGroups[] = $current;
            }
            $current = ['section' => $it['section'], 'items' => []];
        } else {
            $current['items'][] = $it;
        }
    }
    if (! empty($current['items'])) {
        $navGroups[] = $current;
    }
@endphp

<aside
    id="{{ $sidebarId }}"
    class="sidebar bg-white border-end position-fixed {{ $collapsed ? 'collapsed' : '' }} {{ $class }}"
    aria-label="Main navigation"
    data-collapsed="{{ $collapsed ? 'true' : 'false' }}"
>
    <!-- Brand -->
    <div class="sidebar-brand d-flex align-items-center gap-2 px-3 py-3 mb-2">
        <i class="bi bi-mortarboard-fill" style="color: var(--color-primary); font-size: 1.5rem;"></i>
        <a href="{{ route('admin.dashboard') }}" class="fw-bold text-slate-900 text-decoration-none d-flex align-items-center gap-2 flex-grow-1">
            School Admin
        </a>
        {{-- Mobile-only close button (sidebar is always open on desktop) --}}
        <button
            class="btn btn-ghost btn-icon sidebar-close d-lg-none ms-auto"
            type="button"
            aria-label="Close navigation"
        >
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav flex-grow-1 overflow-y-auto" role="navigation" aria-label="Main navigation">
        @foreach($navGroups as $group)
            @php $hasActive = collect($group['items'])->contains(fn ($i) => $i['active'] ?? false); @endphp

            @if($group['section'] === null)
                {{-- Ungrouped (Dashboard) — always visible --}}
                <ul class="nav nav-pills flex-column gap-1 px-2 pt-1" role="list">
                    @foreach($group['items'] as $item)
                        @include('components.partials.nav-link', ['item' => $item])
                    @endforeach
                </ul>
            @else
                <div class="nav-group {{ $hasActive ? 'nav-group-open' : '' }}" data-nav-group="{{ \Illuminate\Support\Str::slug($group['section']) }}">
                    <button type="button" class="nav-group-toggle" aria-expanded="{{ $hasActive ? 'true' : 'false' }}">
                        <span class="nav-group-title">{{ $group['section'] }}</span>
                        <i class="bi bi-chevron-down nav-group-caret" aria-hidden="true"></i>
                    </button>
                    <ul class="nav nav-pills flex-column gap-1 px-2 nav-group-items" role="list">
                        @foreach($group['items'] as $item)
                            @include('components.partials.nav-link', ['item' => $item])
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </nav>

    <!-- Footer / User -->
    @if($user || $footer)
        <div class="sidebar-footer border-top pt-3 px-3 mt-auto">
            @if($user)
                <div class="d-flex align-items-center gap-3">
                    @if($user['avatar'] ?? false)
                        <img src="{{ $user['avatar'] }}" alt="" class="avatar avatar-sm" />
                    @else
                        <div class="avatar avatar-sm bg-primary-light text-primary">
                            {{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                    @if(!$collapsed)
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-medium text-truncate">{{ $user['name'] }}</div>
                            @if(isset($user['role']))
                                <div class="text-xs text-muted">{{ $user['role'] }}</div>
                            @endif
                        @endif
                    </div>
                    @if(!$collapsed && isset($user['menu']))
                        <div class="dropdown ms-auto">
                            <button class="btn btn-ghost btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User menu">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                {{ $user['menu'] }}
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
            @if($footer)
                <div class="mt-3">
                    {{ $footer }}
                </div>
            @endif
        </div>
    @endif
</aside>

<!-- Backdrop for mobile -->
<div class="sidebar-backdrop" data-bs-toggle="sidebar" aria-hidden="true"></div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.getElementById('{{ $sidebarId }}');
        var backdrop = document.querySelector('.sidebar-backdrop');
        var closeBtn = sidebar?.querySelector('.sidebar-close');

        if (!sidebar) return;

        // The sidebar is always open on desktop. On mobile it slides in as an
        // off-canvas panel, opened by the header hamburger and closed here.
        function closeMobile() {
            sidebar.classList.remove('show');
            if (backdrop) backdrop.style.display = 'none';
        }

        if (backdrop) backdrop.addEventListener('click', closeMobile);
        if (closeBtn) closeBtn.addEventListener('click', closeMobile);

        // Header hamburger dispatches sidebar:toggle {show: true}
        document.addEventListener('sidebar:toggle', function(e) {
            if (e.detail && typeof e.detail.show !== 'undefined') {
                sidebar.classList.toggle('show', e.detail.show);
                if (backdrop) backdrop.style.display = e.detail.show ? 'block' : 'none';
            }
        });

        // Close the mobile panel after navigating to a link
        sidebar.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) closeMobile();
            });
        });

        // ── Collapsible section groups (exclusive accordion) ────────────────
        // Only one section is open at a time. On a section page, the active section
        // is shown; on Dashboard, the last-opened section is restored.
        var STORE_KEY = 'sidebar-open-group';
        var groups = Array.prototype.slice.call(sidebar.querySelectorAll('.nav-group'));

        function setOpen(target) {
            groups.forEach(function(g) {
                var on = g === target;
                g.classList.toggle('nav-group-open', on);
                var t = g.querySelector('.nav-group-toggle');
                if (t) t.setAttribute('aria-expanded', on ? 'true' : 'false');
            });
        }

        var activeGroup = groups.find(function(g) { return g.querySelector('.nav-link.active'); });
        if (activeGroup) {
            setOpen(activeGroup);
        } else {
            var savedKey = null;
            try { savedKey = localStorage.getItem(STORE_KEY); } catch (e) {}
            var saved = savedKey && groups.find(function(g) { return g.getAttribute('data-nav-group') === savedKey; });
            setOpen(saved || null);
        }

        groups.forEach(function(group) {
            var toggle = group.querySelector('.nav-group-toggle');
            if (!toggle) return;
            toggle.addEventListener('click', function() {
                var willOpen = !group.classList.contains('nav-group-open');
                setOpen(willOpen ? group : null);
                try {
                    if (willOpen) localStorage.setItem(STORE_KEY, group.getAttribute('data-nav-group'));
                    else localStorage.removeItem(STORE_KEY);
                } catch (e) {}
            });
        });
    });
</script>
@endpush