{{-- Modern Sidebar Component — module tree (parents + expandable children) --}}
@props([
    'collapsed' => false,
    'isAdmin' => false,
    'canFinance' => false,
    'enabledModules' => [],
    'currentRoute' => null,
    'footer' => null,
    'class' => '',
])

@php
    $sidebarId = 'sidebar-' . uniqid();

    // Build a module tree. Each node is either a direct link
    // (has 'href') or an expandable parent (has 'children').
    $navTree = [];

    $navTree[] = ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')];

    if ($isAdmin) {
        $navTree[] = ['label' => 'Students', 'icon' => 'bi-people-fill', 'children' => [
            ['label' => 'All students', 'href' => route('admin.students.index'), 'active' => request()->routeIs('admin.students.*')],
            ['label' => 'Admissions', 'href' => route('admin.admissions.index'), 'active' => request()->routeIs('admin.admissions.*')],
            ['label' => 'Data import', 'href' => route('admin.data-import.index'), 'active' => request()->routeIs('admin.data-import.*')],
        ]];
        $navTree[] = ['label' => 'Staff', 'icon' => 'bi-person-badge', 'children' => [
            ['label' => 'All staff', 'href' => route('admin.staff.index'), 'active' => request()->routeIs('admin.staff.*')],
            ['label' => 'Designations', 'href' => route('admin.designations.index'), 'active' => request()->routeIs('admin.designations.*')],
            ['label' => 'Departments', 'href' => route('admin.departments.index'), 'active' => request()->routeIs('admin.departments.*')],
        ]];
        $navTree[] = ['label' => 'Academic', 'icon' => 'bi-mortarboard', 'children' => [
            ['label' => 'Academic years', 'href' => route('admin.academic-years.index'), 'active' => request()->routeIs('admin.academic-years.*')],
            ['label' => 'Classes & sections', 'href' => route('admin.classes.index'), 'active' => request()->routeIs('admin.classes.*') || request()->routeIs('admin.sections.*')],
            ['label' => 'Subjects', 'href' => route('admin.subjects.index'), 'active' => request()->routeIs('admin.subjects.*')],
            ['label' => 'Groups', 'href' => route('admin.groups.index'), 'active' => request()->routeIs('admin.groups.*')],
            ['label' => 'Versions', 'href' => route('admin.versions.index'), 'active' => request()->routeIs('admin.versions.*')],
            ['label' => 'Shifts', 'href' => route('admin.shifts.index'), 'active' => request()->routeIs('admin.shifts.*')],
            ['label' => 'Class routine', 'href' => route('admin.routine.index'), 'active' => request()->routeIs('admin.routine.*') || request()->routeIs('admin.routine-setup.*')],
        ]];
        $navTree[] = ['label' => 'Attendance', 'icon' => 'bi-calendar-check', 'href' => route('admin.attendance.index'), 'active' => request()->routeIs('admin.attendance.*')];
        $navTree[] = ['label' => 'Examinations', 'icon' => 'bi-journal-text', 'children' => [
            ['label' => 'Exam types', 'href' => route('admin.exam-types.index'), 'active' => request()->routeIs('admin.exam-types.*')],
            ['label' => 'Exams', 'href' => route('admin.exams.index'), 'active' => request()->routeIs('admin.exams.*') || request()->routeIs('admin.exam-marks.*')],
            ['label' => 'Mark settings', 'href' => route('admin.mark-settings.index'), 'active' => request()->routeIs('admin.mark-settings.*')],
            ['label' => 'Exam halls', 'href' => route('admin.exam-halls.index'), 'active' => request()->routeIs('admin.exam-halls.*')],
        ]];
    }

    if ($canFinance) {
        $navTree[] = ['label' => 'Finance', 'icon' => 'bi-cash-coin', 'children' => [
            ['label' => 'Fee categories', 'href' => route('admin.fee-categories.index'), 'active' => request()->routeIs('admin.fee-categories.*')],
            ['label' => 'Fee items', 'href' => route('admin.fee-items.index'), 'active' => request()->routeIs('admin.fee-items.*')],
            ['label' => 'Discounts', 'href' => route('admin.fee-discounts.index'), 'active' => request()->routeIs('admin.fee-discounts.*')],
            ['label' => 'Invoices', 'href' => route('admin.invoices.index'), 'active' => request()->routeIs('admin.invoices.*')],
            ['label' => 'Payments', 'href' => route('admin.payments.index'), 'active' => request()->routeIs('admin.payments.*')],
            ['label' => 'Refunds', 'href' => route('admin.refunds.index'), 'active' => request()->routeIs('admin.refunds.*')],
            ['label' => 'Student credit', 'href' => route('admin.student-credit.index'), 'active' => request()->routeIs('admin.student-credit.*')],
        ]];
        $navTree[] = ['label' => 'Reports', 'icon' => 'bi-graph-up', 'href' => route('admin.reports.fee-collection'), 'active' => request()->routeIs('admin.reports.*')];
    }

    if ($isAdmin) {
        $navTree[] = ['label' => 'Certificates & IDs', 'icon' => 'bi-file-earmark-medical', 'href' => route('admin.testimonials.index'), 'active' => request()->routeIs('admin.testimonials.*') || request()->routeIs('admin.admit-cards.*') || request()->routeIs('admin.cert-templates.*') || request()->routeIs('admin.id-cards.*') || request()->routeIs('admin.id-card-templates.*')];
        $navTree[] = ['label' => 'Communication', 'icon' => 'bi-chat-dots', 'children' => [
            ['label' => 'Announcements', 'href' => route('admin.announcements.index'), 'active' => request()->routeIs('admin.announcements.*')],
            ['label' => 'SMS', 'href' => route('admin.sms.index'), 'active' => request()->routeIs('admin.sms.*')],
            ['label' => 'Messages', 'href' => route('admin.messages.index'), 'active' => request()->routeIs('admin.messages.*')],
            ['label' => 'Enquiries', 'href' => route('admin.enquiries.index'), 'active' => request()->routeIs('admin.enquiries.*')],
        ]];
        $navTree[] = ['label' => 'HR & Leave', 'icon' => 'bi-person-workspace', 'children' => [
            ['label' => 'Leave types', 'href' => route('admin.leave-types.index'), 'active' => request()->routeIs('admin.leave-types.*')],
            ['label' => 'Student leave', 'href' => route('admin.student-leave.index'), 'active' => request()->routeIs('admin.student-leave.*')],
            ['label' => 'Staff leave', 'href' => route('admin.staff-leave.index'), 'active' => request()->routeIs('admin.staff-leave.*')],
            ['label' => 'Staff loans', 'href' => route('admin.staff-loans.index'), 'active' => request()->routeIs('admin.staff-loans.*')],
        ]];
        $navTree[] = ['label' => 'Website', 'icon' => 'bi-window', 'href' => route('admin.pages.index'), 'active' => request()->routeIs('admin.pages.*')];
    }

    if ($isAdmin && $enabledModules) {
        $optChildren = [];
        if (in_array('library', $enabledModules)) {
            $optChildren[] = ['label' => 'Library', 'href' => route('admin.library.books.index'), 'active' => request()->routeIs('admin.library.*')];
        }
        if (in_array('transport', $enabledModules)) {
            $optChildren[] = ['label' => 'Transport', 'href' => route('admin.transport.routes.index'), 'active' => request()->routeIs('admin.transport.*')];
        }
        if (in_array('payroll', $enabledModules)) {
            $optChildren[] = ['label' => 'Payroll', 'href' => route('admin.payroll.runs.index'), 'active' => request()->routeIs('admin.payroll.*')];
        }
        if (in_array('lms', $enabledModules)) {
            $optChildren[] = ['label' => 'LMS', 'href' => route('admin.lms.courses.index'), 'active' => request()->routeIs('admin.lms.*')];
        }
        if ($optChildren) {
            $navTree[] = ['label' => 'Modules', 'icon' => 'bi-puzzle', 'children' => $optChildren];
        }
    }

    if ($isAdmin) {
        $navTree[] = ['label' => 'Settings', 'icon' => 'bi-gear', 'children' => [
            ['label' => 'School settings', 'href' => route('admin.school.edit'), 'active' => request()->routeIs('admin.school.*') || request()->routeIs('admin.modules.*')],
            ['label' => 'Payment settings', 'href' => route('admin.payment-config.edit'), 'active' => request()->routeIs('admin.payment-config.*')],
            ['label' => 'Users & roles', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
        ]];
    }
@endphp

<aside
    id="{{ $sidebarId }}"
    class="sidebar bg-white border-end position-fixed {{ $class }}"
    aria-label="Main navigation"
>
    <!-- Header/Brand -->
    <div class="sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-brand" aria-label="School Admin Dashboard">
            <span class="brand-icon">
                <i class="bi bi-mortarboard-fill" aria-hidden="true"></i>
            </span>
            <span class="brand-text">School Admin</span>
        </a>
        <button class="btn sidebar-close d-lg-none" type="button" aria-label="Close navigation">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav" role="navigation" aria-label="Main navigation">
        <ul role="list">
            @foreach($navTree as $node)
                @if(empty($node['children']))
                    {{-- Direct link --}}
                    <li class="nav-item">
                        <a href="{{ $node['href'] }}" class="nav-link {{ ($node['active'] ?? false) ? 'active' : '' }}"
                           aria-current="{{ ($node['active'] ?? false) ? 'page' : 'false' }}">
                            <i class="bi {{ $node['icon'] }} nav-icon" aria-hidden="true"></i>
                            <span class="nav-label flex-grow-1">{{ $node['label'] }}</span>
                        </a>
                    </li>
                @else
                    @php $childActive = collect($node['children'])->contains(fn ($c) => $c['active'] ?? false); @endphp
                    <li class="nav-item nav-parent {{ $childActive ? 'open' : '' }}" data-nav-parent="{{ \Illuminate\Support\Str::slug($node['label']) }}">
                        <button type="button" class="nav-link nav-parent-toggle {{ $childActive ? 'has-active' : '' }}" aria-expanded="{{ $childActive ? 'true' : 'false' }}">
                            <i class="bi {{ $node['icon'] }} nav-icon" aria-hidden="true"></i>
                            <span class="nav-label flex-grow-1">{{ $node['label'] }}</span>
                            <i class="bi bi-chevron-down nav-caret" aria-hidden="true"></i>
                        </button>
                        <ul class="nav-children" role="list">
                            @foreach($node['children'] as $child)
                                <li class="nav-item">
                                    <a href="{{ $child['href'] }}" class="nav-link nav-child {{ ($child['active'] ?? false) ? 'active' : '' }}"
                                       aria-current="{{ ($child['active'] ?? false) ? 'page' : 'false' }}">
                                        <span class="nav-label flex-grow-1">{{ $child['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>

    <!-- Footer (optional custom content only) -->
    @if($footer)
        <div class="sidebar-footer">
            {{ $footer }}
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

        // Mobile off-canvas handling
        function closeMobile() {
            sidebar.classList.remove('show');
            if (backdrop) backdrop.style.display = 'none';
        }
        if (backdrop) backdrop.addEventListener('click', closeMobile);
        if (closeBtn) closeBtn.addEventListener('click', closeMobile);
        document.addEventListener('sidebar:toggle', function(e) {
            if (e.detail && typeof e.detail.show !== 'undefined') {
                sidebar.classList.toggle('show', e.detail.show);
                if (backdrop) backdrop.style.display = e.detail.show ? 'block' : 'none';
            }
        });
        sidebar.querySelectorAll('.nav-link:not(.nav-parent-toggle)').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) closeMobile();
            });
        });

        // ── Expandable parents (exclusive: one open at a time) ──────────────
        var STORE_KEY = 'sidebar-open-parent';
        var parents = Array.prototype.slice.call(sidebar.querySelectorAll('.nav-parent'));

        function setOpen(target) {
            parents.forEach(function(p) {
                var on = p === target;
                p.classList.toggle('open', on);
                var t = p.querySelector('.nav-parent-toggle');
                if (t) t.setAttribute('aria-expanded', on ? 'true' : 'false');
            });
        }

        var activeParent = parents.find(function(p) { return p.querySelector('.nav-child.active'); });
        if (activeParent) {
            setOpen(activeParent);
        } else {
            var savedKey = null;
            try { savedKey = localStorage.getItem(STORE_KEY); } catch (e) {}
            var saved = savedKey && parents.find(function(p) { return p.getAttribute('data-nav-parent') === savedKey; });
            setOpen(saved || null);
        }

        parents.forEach(function(parent) {
            var toggle = parent.querySelector('.nav-parent-toggle');
            if (!toggle) return;
            toggle.addEventListener('click', function() {
                var willOpen = !parent.classList.contains('open');
                setOpen(willOpen ? parent : null);
                try {
                    if (willOpen) localStorage.setItem(STORE_KEY, parent.getAttribute('data-nav-parent'));
                    else localStorage.removeItem(STORE_KEY);
                } catch (e) {}
            });
        });
    });
</script>
@endpush
