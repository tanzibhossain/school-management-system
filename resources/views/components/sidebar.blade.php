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

    $navTree[] = ['label' => __('Dashboard'), 'icon' => 'bi-speedometer2', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')];

    if ($isAdmin) {
        $navTree[] = ['label' => __('Students'), 'key' => 'students', 'icon' => 'bi-people-fill', 'children' => [
            ['label' => __('All students'), 'href' => route('admin.students.index'), 'active' => request()->routeIs('admin.students.*')],
            ['label' => __('Admissions'), 'href' => route('admin.admissions.index'), 'active' => request()->routeIs('admin.admissions.*')],
            ['label' => __('Data import'), 'href' => route('admin.data-import.index'), 'active' => request()->routeIs('admin.data-import.*')],
        ]];
        $navTree[] = ['label' => __('Staff'), 'key' => 'staff', 'icon' => 'bi-person-badge', 'children' => [
            ['label' => __('All staff'), 'href' => route('admin.staff.index'), 'active' => request()->routeIs('admin.staff.*')],
            ['label' => __('Designations'), 'href' => route('admin.designations.index'), 'active' => request()->routeIs('admin.designations.*')],
            ['label' => __('Departments'), 'href' => route('admin.departments.index'), 'active' => request()->routeIs('admin.departments.*')],
        ]];
        $navTree[] = ['label' => __('Academic'), 'key' => 'academic', 'icon' => 'bi-mortarboard', 'children' => [
            ['label' => __('Academic years'), 'href' => route('admin.academic-years.index'), 'active' => request()->routeIs('admin.academic-years.*')],
            ['label' => __('Classes & sections'), 'href' => route('admin.classes.index'), 'active' => request()->routeIs('admin.classes.*') || request()->routeIs('admin.sections.*')],
            ['label' => __('Subjects'), 'href' => route('admin.subjects.index'), 'active' => request()->routeIs('admin.subjects.*')],
            ['label' => __('Groups'), 'href' => route('admin.groups.index'), 'active' => request()->routeIs('admin.groups.*')],
            ['label' => __('Versions'), 'href' => route('admin.versions.index'), 'active' => request()->routeIs('admin.versions.*')],
            ['label' => __('Shifts'), 'href' => route('admin.shifts.index'), 'active' => request()->routeIs('admin.shifts.*')],
            ['label' => __('Class routine'), 'href' => route('admin.routine.index'), 'active' => request()->routeIs('admin.routine.*') || request()->routeIs('admin.routine-setup.*')],
        ]];
        $navTree[] = ['label' => __('Attendance'), 'icon' => 'bi-calendar-check', 'href' => route('admin.attendance.index'), 'active' => request()->routeIs('admin.attendance.*')];
        $navTree[] = ['label' => __('Examinations'), 'key' => 'examinations', 'icon' => 'bi-journal-text', 'children' => [
            ['label' => __('Exam types'), 'href' => route('admin.exam-types.index'), 'active' => request()->routeIs('admin.exam-types.*')],
            ['label' => __('Exams'), 'href' => route('admin.exams.index'), 'active' => request()->routeIs('admin.exams.*') || request()->routeIs('admin.exam-marks.*')],
            ['label' => __('Mark settings'), 'href' => route('admin.mark-settings.index'), 'active' => request()->routeIs('admin.mark-settings.*')],
            ['label' => __('Exam halls'), 'href' => route('admin.exam-halls.index'), 'active' => request()->routeIs('admin.exam-halls.*')],
        ]];
    }

    if ($canFinance) {
        $navTree[] = ['label' => __('Finance'), 'key' => 'finance', 'icon' => 'bi-cash-coin', 'children' => [
            ['label' => __('Fee categories'), 'href' => route('admin.fee-categories.index'), 'active' => request()->routeIs('admin.fee-categories.*')],
            ['label' => __('Fee items'), 'href' => route('admin.fee-items.index'), 'active' => request()->routeIs('admin.fee-items.*')],
            ['label' => __('Discounts'), 'href' => route('admin.fee-discounts.index'), 'active' => request()->routeIs('admin.fee-discounts.*')],
            ['label' => __('Invoices'), 'href' => route('admin.invoices.index'), 'active' => request()->routeIs('admin.invoices.*')],
            ['label' => __('Payments'), 'href' => route('admin.payments.index'), 'active' => request()->routeIs('admin.payments.*')],
            ['label' => __('Refunds'), 'href' => route('admin.refunds.index'), 'active' => request()->routeIs('admin.refunds.*')],
            ['label' => __('Student credit'), 'href' => route('admin.student-credit.index'), 'active' => request()->routeIs('admin.student-credit.*')],
        ]];
        $navTree[] = ['label' => __('Reports'), 'icon' => 'bi-graph-up', 'href' => route('admin.reports.fee-collection'), 'active' => request()->routeIs('admin.reports.*')];
    }

    if ($isAdmin) {
        $navTree[] = ['label' => __('Certificates & IDs'), 'icon' => 'bi-file-earmark-medical', 'href' => route('admin.testimonials.index'), 'active' => request()->routeIs('admin.testimonials.*') || request()->routeIs('admin.admit-cards.*') || request()->routeIs('admin.cert-templates.*') || request()->routeIs('admin.id-cards.*') || request()->routeIs('admin.id-card-templates.*')];
        $navTree[] = ['label' => __('Communication'), 'key' => 'communication', 'icon' => 'bi-chat-dots', 'children' => [
            ['label' => __('Announcements'), 'href' => route('admin.announcements.index'), 'active' => request()->routeIs('admin.announcements.*')],
            ['label' => __('SMS'), 'href' => route('admin.sms.index'), 'active' => request()->routeIs('admin.sms.*')],
            ['label' => __('Messages'), 'href' => route('admin.messages.index'), 'active' => request()->routeIs('admin.messages.*')],
            ['label' => __('Enquiries'), 'href' => route('admin.enquiries.index'), 'active' => request()->routeIs('admin.enquiries.*')],
        ]];
        $navTree[] = ['label' => __('HR & Leave'), 'key' => 'hr-leave', 'icon' => 'bi-person-workspace', 'children' => [
            ['label' => __('Leave types'), 'href' => route('admin.leave-types.index'), 'active' => request()->routeIs('admin.leave-types.*')],
            ['label' => __('Student leave'), 'href' => route('admin.student-leave.index'), 'active' => request()->routeIs('admin.student-leave.*')],
            ['label' => __('Staff leave'), 'href' => route('admin.staff-leave.index'), 'active' => request()->routeIs('admin.staff-leave.*')],
            ['label' => __('Staff loans'), 'href' => route('admin.staff-loans.index'), 'active' => request()->routeIs('admin.staff-loans.*')],
        ]];
        $navTree[] = ['label' => __('Website'), 'key' => 'website', 'icon' => 'bi-window', 'children' => [
            ['label' => __('Pages'), 'href' => route('admin.pages.index'), 'active' => request()->routeIs('admin.pages.*')],
            ['label' => __('Page Templates'), 'href' => route('admin.page-templates.index'), 'active' => request()->routeIs('admin.page-templates.*')],
            ['label' => __('Menus'), 'href' => route('admin.menus.index'), 'active' => request()->routeIs('admin.menus.*')],
        ]];
    }

    if ($isAdmin && $enabledModules) {
        $optChildren = [];
        if (in_array('library', $enabledModules)) {
            $optChildren[] = ['label' => __('Library'), 'href' => route('admin.library.books.index'), 'active' => request()->routeIs('admin.library.*')];
        }
        if (in_array('transport', $enabledModules)) {
            $optChildren[] = ['label' => __('Transport'), 'href' => route('admin.transport.routes.index'), 'active' => request()->routeIs('admin.transport.*')];
        }
        if (in_array('payroll', $enabledModules)) {
            $optChildren[] = ['label' => __('Payroll'), 'href' => route('admin.payroll.runs.index'), 'active' => request()->routeIs('admin.payroll.*')];
        }
        if (in_array('lms', $enabledModules)) {
            $optChildren[] = ['label' => __('LMS'), 'href' => route('admin.lms.courses.index'), 'active' => request()->routeIs('admin.lms.*')];
        }
        if ($optChildren) {
            $navTree[] = ['label' => __('Modules'), 'key' => 'modules', 'icon' => 'bi-puzzle', 'children' => $optChildren];
        }
    }

    if ($isAdmin) {
        $navTree[] = ['label' => __('Settings'), 'key' => 'settings', 'icon' => 'bi-gear', 'children' => [
            ['label' => __('School settings'), 'href' => route('admin.school.edit'), 'active' => request()->routeIs('admin.school.*') || request()->routeIs('admin.modules.*')],
            ['label' => __('Languages'), 'href' => route('admin.languages.index'), 'active' => request()->routeIs('admin.languages.*')],
            ['label' => __('Payment settings'), 'href' => route('admin.payment-config.edit'), 'active' => request()->routeIs('admin.payment-config.*')],
            ['label' => __('Users & roles'), 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
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
            <span class="brand-text">{{ __('School Admin') }}</span>
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
                    <li class="nav-item nav-parent {{ $childActive ? 'open' : '' }}" data-nav-parent="{{ $node['key'] ?? \Illuminate\Support\Str::slug($node['label']) }}">
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
