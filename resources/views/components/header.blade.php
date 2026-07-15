{{-- Modern Header Component --}}
@props([
    'user' => null,
    'notifications' => [],
    'searchable' => true,
    'quickActions' => [],
    'class' => '',
])

@php
    $headerId = 'header-' . uniqid();
@endphp

<header
    id="{{ $headerId }}"
    class="page-head border-bottom sticky-top {{ $class }}"
    style="background: var(--color-surface); height: var(--header-height); z-index: var(--z-sticky);"
    role="banner"
>
    <div class="container-fluid px-3 px-lg-4 h-100">
        <div class="d-flex align-items-center justify-content-between h-100 gap-3">
            <!-- Mobile Sidebar Toggle -->
            <button
                class="btn btn-ghost btn-icon d-lg-none"
                type="button"
                aria-label="Toggle navigation"
                aria-expanded="false"
                aria-controls="sidebar-main"
                onclick="document.dispatchEvent(new CustomEvent('sidebar:toggle', { detail: { show: true } }))"
            >
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <!-- Global Search -->
            @if($searchable)
                <div class="header-search flex-grow-1 flex-lg-grow-0 mx-3 mx-lg-4" style="max-width: 400px;">
                    <div class="position-relative">
                        <label for="global-search" class="visually-hidden">Search</label>
                        <input
                            type="search"
                            id="global-search"
                            class="form-input form-input-sm ps-4"
                            placeholder="Search... (⌘K)"
                            autocomplete="off"
                            aria-label="Search"
                            aria-expanded="false"
                            aria-controls="search-results"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                        >
                        <i class="bi bi-search position-absolute start-0 top-50 translate-middle-y ms-3 text-muted" aria-hidden="true"></i>

                        <!-- Search Results Dropdown -->
                        <div class="dropdown-menu dropdown-menu-end w-100 mt-2 shadow-lg" id="search-results" style="display: none;">
                            <div class="dropdown-header">Quick Actions</div>
                            <a class="dropdown-item" href="{{ route('admin.students.create') }}"><i class="bi bi-person-plus me-2"></i> New Student</a>
                            <a class="dropdown-item" href="{{ route('admin.staff.store') }}"><i class="bi bi-person-badge-plus me-2"></i> New Staff</a>
                            <a class="dropdown-item" href="{{ route('admin.admissions.index') }}"><i class="bi bi-clipboard-check me-2"></i> Admissions</a>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header">Recent</div>
                            <a class="dropdown-item text-muted" href="#">No recent searches</a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Right Actions -->
            <div class="d-flex align-items-center gap-1 gap-lg-2 ms-auto">
                <!-- Notifications -->
                <div class="dropdown position-relative">
                    <button
                        class="btn btn-ghost btn-icon position-relative"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Notifications"
                    >
                        <i class="bi bi-bell" aria-hidden="true"></i>
                        @if(count($notifications) > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ count($notifications) > 9 ? '9+' : count($notifications) }}
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        @endif
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 360px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notifications</span>
                            @if(count($notifications) > 0)
                                <a href="#" class="text-xs text-primary">Mark all read</a>
                            @endif
                        </li>
                        @if(empty($notifications))
                            <li><span class="dropdown-item text-muted text-center py-4">No new notifications</span></li>
                        @else
                            @foreach($notifications as $notification)
                                <li>
                                    <a class="dropdown-item d-flex align-items-start gap-3 py-3" href="{{ $notification['url'] ?? '#' }}">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-light text-primary d-flex align-items-center justify-content-center">
                                            <i class="bi {{ $notification['icon'] ?? 'bi-bell' }}" aria-hidden="true"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <p class="mb-1 fw-medium text-sm">{{ $notification['title'] }}</p>
                                            <p class="mb-0 text-xs text-muted">{{ $notification['time'] }}</p>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center text-primary" href="#">View all notifications</a></li>
                        @endif
                    </ul>
                </div>

                <!-- Quick Actions -->
                @if(!empty($quickActions))
                    <div class="dropdown d-lg-none">
                        <button class="btn btn-ghost btn-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Quick actions">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                            @foreach($quickActions as $action)
                                <li><a class="dropdown-item" href="{{ $action['url'] }}"><i class="bi {{ $action['icon'] }} me-2"></i>{{ $action['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- User Menu -->
                <div class="dropdown">
                    <button
                        class="btn btn-ghost d-flex align-items-center gap-2 py-1 px-2"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="User menu"
                    >
                        <div class="avatar avatar-sm">
                            @if($user['avatar'] ?? false)
                                <img src="{{ $user['avatar'] }}" alt="" />
                            @else
                                <span class="text-primary fw-semibold">{{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}</span>
                            @endif
                        </div>
                        <span class="d-none d-md-inline-block fw-medium text-truncate" style="max-width: 120px;">{{ $user['name'] ?? 'User' }}</span>
                        <i class="bi bi-chevron-down d-none d-md-inline-block text-muted" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 220px;">
                        <li class="dropdown-header px-3 py-2">
                            <div class="fw-medium">{{ $user['name'] }}</div>
                            <div class="text-xs text-muted">{{ $user['email'] ?? '' }}</div>
                            @if(isset($user['role']))
                                <span class="badge badge-sm badge-neutral mt-1">{{ $user['role'] }}</span>
                            @endif
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('admin.users.index') }}"><i class="bi bi-person me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger w-100 text-start">
                                    <i class="bi bi-box-arrow-right me-2"></i> Sign out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Global search dropdown
            var searchInput = document.getElementById('global-search');
            var searchDropdown = document.getElementById('search-results');

            if (searchInput && searchDropdown) {
                searchInput.addEventListener('focus', function() {
                    if (this.value.length === 0) {
                        searchDropdown.style.display = 'block';
                    }
                });

                searchInput.addEventListener('input', function() {
                    // Here you would filter results based on input
                    // For now, just show/hide based on input
                    searchDropdown.style.display = this.value.length > 0 ? 'block' : 'block';
                });

                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.blur();
                        searchDropdown.style.display = 'none';
                    }
                });

                // Close on outside click
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.header-search')) {
                        searchDropdown.style.display = 'none';
                    }
                });

                // Keyboard shortcut ⌘K / Ctrl+K
                document.addEventListener('keydown', function(e) {
                    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                        e.preventDefault();
                        searchInput.focus();
                    }
                });
            });

            // Notification dropdown click outside handling
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                        var toggle = menu.previousElementSibling;
                        if (toggle && toggle.classList.contains('dropdown-toggle')) {
                            bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
                        }
                    });
                }
            });
        });
    </script>
    @endpush