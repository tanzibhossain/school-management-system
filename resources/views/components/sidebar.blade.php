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
        $navItems[] = ['label' => 'School settings', 'icon' => 'bi-building-gear', 'href' => route('admin.school.edit'), 'active' => request()->routeIs('admin.school.*')];
        $navItems[] = ['label' => 'Modules', 'icon' => 'bi-toggles', 'href' => route('admin.modules.index'), 'active' => request()->routeIs('admin.modules.*')];
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

        if ($canFinance) {
            $navItems[] = ['section' => 'Reports'];
            $navItems[] = ['label' => 'Reports', 'icon' => 'bi-file-earmark-bar-graph', 'href' => route('admin.reports.fee-collection'), 'active' => request()->routeIs('admin.reports.*')];
        }
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
    // Collapse is client-side (toggle + localStorage); keep $collapsed as a boolean and let
    // CSS (.sidebar / .sidebar.collapsed) control the width — no inline width to override.
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
        @if(!$collapsed)
            <a href="{{ route('admin.dashboard') }}" class="fw-bold text-slate-900 text-decoration-none d-flex align-items-center gap-2 flex-grow-1">
                School Admin
            </a>
        @endif
        <button
            class="btn btn-ghost btn-icon sidebar-toggle ms-auto"
            type="button"
            aria-label="{{ $collapsed ? 'Expand sidebar' : 'Collapse sidebar' }}"
            aria-expanded="{{ $collapsed }}"
            aria-controls="{{ $sidebarId }}"
            data-bs-toggle="tooltip"
            data-bs-placement="right"
            title="{{ $collapsed ? 'Expand sidebar' : 'Collapse sidebar' }}"
        >
            <i class="bi {{ $collapsed ? 'bi-chevron-right' : 'bi-chevron-left' }}" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav flex-grow-1 overflow-y-auto" role="navigation" aria-label="Main navigation">
        <ul class="nav nav-pills flex-column gap-1 px-2" role="list">
            @foreach($navItems as $item)
                @if(isset($item['section']))
                    <li class="nav-section px-2 py-1">
                        <span class="text-uppercase text-muted small fw-semibold tracking-wider">
                            {{ $item['section'] }}
                        </span>
                    </li>
                @else
                    @php
                        $itemId = 'nav-' . uniqid();
                        $isActive = $item['active'] ?? false;
                        $itemClasses = ['nav-link'];
                        if ($isActive) $itemClasses[] = 'active';
                        if (isset($item['disabled']) && $item['disabled']) $itemClasses[] = 'disabled';
                        $itemClassString = implode(' ', $itemClasses);

                        $href = $item['href'] ?? '#';
                        $icon = $item['icon'] ?? 'bi-circle';
                        $badge = $item['badge'] ?? null;
                        $tooltip = $item['tooltip'] ?? $item['label'];
                    @endphp
                    <li class="nav-item" role="none">
                        <a
                            id="{{ $itemId }}"
                            href="{{ $href }}"
                            class="{{ $itemClassString }}"
                            role="menuitem"
                            aria-current="{{ $isActive ? 'page' : 'false' }}"
                            aria-disabled="{{ isset($item['disabled']) && $item['disabled'] ? 'true' : 'false' }}"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            title="{{ $tooltip }}"
                            @if(isset($item['disabled']) && $item['disabled']) tabindex="-1" aria-disabled="true" @endif
                        >
                            <i class="bi {{ $icon }} nav-icon" aria-hidden="true"></i>
                            @if(!$collapsed)
                                <span class="nav-label flex-grow-1">{{ $item['label'] }}</span>
                                @if($badge)
                                    <span class="badge badge-sm ms-auto">{{ $badge }}</span>
                                @endif
                            @endif
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
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
        var toggle = sidebar?.querySelector('.sidebar-toggle');
        var backdrop = document.querySelector('.sidebar-backdrop');

        if (!sidebar || !toggle) return;

        // Toggle collapse
        toggle.addEventListener('click', function() {
            var isCollapsed = sidebar.classList.toggle('collapsed');
            toggle.setAttribute('aria-expanded', isCollapsed);
            toggle.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
            toggle.title = isCollapsed ? 'Expand sidebar' : 'Collapse sidebar';
            toggle.querySelector('i').className = 'bi ' + (isCollapsed ? 'bi-chevron-right' : 'bi-chevron-left');

            // Persist to localStorage
            localStorage.setItem('sidebar-collapsed', isCollapsed);

            // Update backdrop
            if (backdrop) {
                backdrop.style.display = isCollapsed ? 'none' : (window.innerWidth < 992 ? 'block' : 'none');
            }

            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('sidebar:toggle', { detail: { collapsed: isCollapsed } }));
        });

        // Mobile backdrop click
        if (backdrop) {
            backdrop.addEventListener('click', function() {
                sidebar.classList.remove('show');
                backdrop.style.display = 'none';
            });
        }

        // Mobile toggle from header
        document.addEventListener('sidebar:toggle', function(e) {
            if (e.detail && typeof e.detail.show !== 'undefined') {
                sidebar.classList.toggle('show', e.detail.show);
                if (backdrop) backdrop.style.display = e.detail.show ? 'block' : 'none';
            }
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(sidebar.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(el) {
            new bootstrap.Tooltip(el, {
                boundary: document.body,
                delay: { show: 300, hide: 100 }
            });
        });

        // Restore collapse state
        var savedCollapsed = localStorage.getItem('sidebar-collapsed');
        if (savedCollapsed === 'true' && !sidebar.classList.contains('collapsed')) {
            toggle.click();
        } else if (savedCollapsed === 'false' && sidebar.classList.contains('collapsed')) {
            toggle.click();
        }
    });
</script>
@endpush